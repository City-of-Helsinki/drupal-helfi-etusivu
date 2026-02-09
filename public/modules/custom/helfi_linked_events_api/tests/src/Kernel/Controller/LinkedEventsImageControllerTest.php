<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_linked_events_api\Kernel\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\helfi_linked_events_api\Controller\LinkedEventsImageController;
use Drupal\image\ImageStyleInterface;
use Drupal\image\ImageStyleStorageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests LinkedEventsImageController.
 */
#[Group('helfi_linked_events_api')]
#[CoversClass(LinkedEventsImageController::class)]
#[RunTestsInSeparateProcesses]
class LinkedEventsImageControllerTest extends KernelTestBase {

  use ApiTestTrait;

  const LINKED_EVENTS_IMAGE_URL = 'https://example.com/image.jpg';
  const LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME = '2026-02-06T07:29:43.686092Z';
  const STYLE_IMAGE_URL = 'https://localhost/style/image.jpg';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image',
    'imagecache_external',
    'helfi_linked_events_api',
  ];

  /**
   * Mocked cache backend.
   */
  protected ObjectProphecy $cache;

  /**
   * Mocked image style.
   */
  protected ObjectProphecy $imageStyle;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cache = $this->prophesize(CacheBackendInterface::class);
    $this->cache->get(Argument::any())->willReturn(NULL);

    $this->imageStyle = $this->prophesize(ImageStyleInterface::class);
    $this->imageStyle->supportsUri(Argument::any())->willReturn(FALSE);
    $this->imageStyle->supportsUri('public://externals/123.jpg')->willReturn(TRUE);
    $this->imageStyle->buildUrl('public://externals/123.jpg')->willReturn('https://localhost/style/image.jpg');
  }

  /**
   * Tests access to the controller.
   */
  public function testAccess() : void {
    $request = $this->getMockedRequest('/linked-events/image/123');
    $response = $this->processRequest($request);
    $this->assertEquals(404, $response->getStatusCode());
  }

  /**
   * Tests deliver method.
   */
  #[DataProvider('providerDeliver')]
  public function testDeliver(
    string $image_id,
    string $image_style,
    string $time,
    bool $is_redict = TRUE,
    bool $is_linked_events_success = TRUE,
    bool $is_download_external_success = TRUE,
    bool $is_download_external_with_cachebust = TRUE,
    bool $is_image_style_success = TRUE,
  ) : void {
    $imageStyleStorage = $this->prophesize(ImageStyleStorageInterface::class);
    $imageStyleStorage->load(Argument::any())->willReturn($this->imageStyle->reveal());
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('image_style')->willReturn($imageStyleStorage->reveal());
    $this->container->set('entity_type.manager', $entityTypeManager->reveal());

    // Linked Events mock response.
    if ($is_linked_events_success) {
      $this->setupMockHttpClient([
        new Response(body: json_encode([
          'url' => self::LINKED_EVENTS_IMAGE_URL,
          'last_modified_time' => self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME,
        ])),
      ]);
    }
    else {
      $this->setupMockHttpClient([
        new RequestException("Test failure", new Psr7Request('GET', 'https://api.hel.fi/linkedevents/v1/image/123'), new Response(504)),
      ]);
    }

    // Image style mock response.
    if ($is_image_style_success) {
      $this->imageStyle->supportsUri(Argument::any())->willReturn(TRUE);
      $this->imageStyle->buildUrl(Argument::any())->willReturn(self::STYLE_IMAGE_URL);
    }
    else {
      $this->imageStyle->supportsUri(Argument::any())->willReturn(FALSE);
      $this->imageStyle->buildUrl(Argument::any())->willReturn('');
    }

    $sut = new LinkedEventsImageControllerSut(
      $this->container->get('entity_type.manager'),
      $this->container->get('http_client'),
      $this->container->get('cache.default'),
    );
    $sut->setImagecacheExternalUrl($is_download_external_success ? self::LINKED_EVENTS_IMAGE_URL : FALSE);

    $response = $sut->deliver(new Request([
      'style' => $image_style,
      'time' => $time,
    ]), $image_id);

    // Test last download url cache busting query parameter.
    if ($last_download_url_parameter = $sut->getLastDownloadUrlParameter()) {
      if ($is_download_external_with_cachebust) {
        $this->assertStringContainsString('time=', $last_download_url_parameter);
      }
      else {
        $this->assertStringNotContainsString('time=', $last_download_url_parameter);
      }
    }

    if ($is_redict) {
      $this->assertInstanceOf(CacheableRedirectResponse::class, $response);
      $this->assertEquals(302, $response->getStatusCode());
      $this->assertEquals(self::STYLE_IMAGE_URL, $response->getTargetUrl());
    }
    else {
      $this->assertEquals(404, $response->getStatusCode());
    }
  }

  /**
   * Data provider for testDeliver().
   */
  public static function providerDeliver(): array {
    return [
      'success' => [
        '123',
        '1.5_511w_341h',
        self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME,
      ],
      'invalid time' => [
        '123',
        '1.5_511w_341h',
        'invalid-time+/',
        FALSE,
      ],
      'invalid image style' => [
        '123',
        'invalid-image-style+/',
        self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME,
        FALSE,
      ],
      'unsupported image style' => [
        '123',
        'unsupported_image_style',
        self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME,
        FALSE,
      ],
      'linked events failure' => [
        '123',
        '1.5_511w_341h',
        self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME,
        FALSE,
        FALSE,
      ],
      'download external failure' => [
        '123',
        '1.5_511w_341h',
        self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME,
        FALSE,
        TRUE,
        FALSE,
        TRUE,
      ],
      'download external without cachebust' => [
        '123',
        '1.5_511w_341h',
        '2026-02-05T07:29:43.686092Z',
        TRUE,
        TRUE,
        TRUE,
        FALSE,
      ],
      'image style failure' => [
        '123',
        '1.5_511w_341h',
        self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME,
        FALSE,
        TRUE,
        TRUE,
        TRUE,
        FALSE,
      ],
    ];
  }

}

/**
 * Sut stub for LinkedEventsImageController.
 */
class LinkedEventsImageControllerSut extends LinkedEventsImageController {

  /**
   * The imagecache external url.
   *
   * @var bool|string
   */
  private bool|string $imagecacheExternalUrl;

  /**
   * Last download url parameter.
   */
  private string $lastDownloadUrlParameter = '';

  /**
   * Sets the imagecache external url for the sut.
   *
   * @param string $url
   *   The imagecache external url.
   */
  public function setImagecacheExternalUrl(bool|string $url): void {
    $this->imagecacheExternalUrl = $url;
  }

  /**
   * Get last download url parameter.
   *
   * @return string
   *   The last download url parameter.
   */
  public function getLastDownloadUrlParameter(): string {
    return $this->lastDownloadUrlParameter;
  }

  /**
   * Stub for downloadExternalImage().
   *
   * Allows to bypass the imagecache_external_generate_path() for tests.
   *
   * @param string $url
   *   The url of the external image.
   *
   * @return bool|string
   *   The uri of the downloaded image or FALSE if the download failed.
   */
  protected function downloadExternalImage(string $url): bool|string {
    $this->lastDownloadUrlParameter = $url;
    return $this->imagecacheExternalUrl;
  }

}
