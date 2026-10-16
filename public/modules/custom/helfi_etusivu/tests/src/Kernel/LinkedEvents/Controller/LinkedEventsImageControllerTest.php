<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\LinkedEvents\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\helfi_etusivu\LinkedEvents\Controller\LinkedEventsImageController;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\ImageStyleInterface;
use Drupal\image\ImageStyleStorageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\TestFileCreationTrait;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests LinkedEventsImageController.
 */
#[Group('helfi_etusivu')]
#[CoversClass(LinkedEventsImageController::class)]
#[RunTestsInSeparateProcesses]
class LinkedEventsImageControllerTest extends KernelTestBase {

  use ApiTestTrait;
  use TestFileCreationTrait;

  const SUPPORTED_IMAGE_STYLE = '1_5_511w_341h';
  const UNSUPPORTED_IMAGE_STYLE = 'unsupported_image_style';
  const LINKED_EVENTS_IMAGE_URL = 'https://example.com/image.jpg';
  const LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME = '2026-02-06T07:29:43.686092Z';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image',
    'imagecache_external',
    'helfi_api_base',
    'diff',
    'helfi_etusivu',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['imagecache_external']);

    ImageStyle::create([
      'name' => self::SUPPORTED_IMAGE_STYLE,
    ])->save();
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
   * Data provider for testDeliver().
   *
   * @return array<mixed>
   *   The data.
   */
  public static function providerDeliver(): array {
    return [
      'success' => [
        '123',
        self::SUPPORTED_IMAGE_STYLE,
        self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME,
      ],
      'missing time' => [
        '123',
        self::SUPPORTED_IMAGE_STYLE,
        '',
        FALSE,
      ],
      'missing image style' => [
        '123',
        '',
        self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME,
        FALSE,
      ],
      'unsupported image style' => [
        '123',
        self::UNSUPPORTED_IMAGE_STYLE,
        self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME,
        FALSE,
      ],
    ];
  }

  /**
   * Tests deliver method with basic parameter variations.
   */
  #[DataProvider('providerDeliver')]
  public function testDeliver(
    string $image_id,
    string $image_style,
    string $time,
    bool $is_redirect = TRUE,
  ) : void {
    $response = $this->callSut($image_id, $image_style, $time);

    if ($is_redirect) {
      $this->assertInstanceOf(TrustedRedirectResponse::class, $response);
      $this->assertEquals(302, $response->getStatusCode());
      $this->assertStringContainsString('files/styles/1_5_511w_341h/public/externals/', $response->getTargetUrl());
    }
    else {
      $this->assertEquals(404, $response->getStatusCode());
    }
  }

  /**
   * Tests deliver method with Linked Events API failure.
   */
  public function testDeliverWithLinkedEventsApiFailure() : void {
    $response = $this->callSut(is_linked_events_api_success: FALSE);

    $this->assertEquals(404, $response->getStatusCode());
  }

  /**
   * Tests deliver method with external image download failure.
   */
  public function testDeliverWithExternalImageDownloadFailure() : void {
    $response = $this->callSut(is_download_external_success: FALSE);

    $this->assertEquals(404, $response->getStatusCode());
  }

  /**
   * Tests deliver method with external image download without cachebust.
   */
  public function testDeliverWithExternalImageDownloadWithoutCachebust() : void {
    $response = $this->callSut(
      apiResponse: [
        'url' => self::LINKED_EVENTS_IMAGE_URL,
      ]
    );

    $this->assertInstanceOf(TrustedRedirectResponse::class, $response);
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString('files/styles/1_5_511w_341h/public/externals/', $response->getTargetUrl());
  }

  /**
   * Tests deliver method with image style failure.
   */
  public function testDeliverWithImageStyleFailure() : void {
    $imageStyle = $this->prophesize(ImageStyleInterface::class);
    $imageStyle->supportsUri(Argument::any())->willReturn(FALSE);
    $imageStyle->buildUrl(Argument::any())->willReturn('');
    $imageStyleStorage = $this->prophesize(ImageStyleStorageInterface::class);
    $imageStyleStorage->load(Argument::any())->willReturn($imageStyle->reveal());
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('image_style')->willReturn($imageStyleStorage->reveal());
    $this->container->set('entity_type.manager', $entityTypeManager->reveal());

    $response = $this->callSut();
    $this->assertEquals(404, $response->getStatusCode());
  }

  /**
   * Creates the controller.
   *
   * @param array<int, \Psr\Http\Message\ResponseInterface|\GuzzleHttp\Exception\GuzzleException> $responses
   *   The HTTP responses.
   * @param array<mixed> $history
   *   The requests sent.
   * @param int|null $requestTime
   *   The request time, or NULL for the current time.
   *
   * @return \Drupal\helfi_etusivu\LinkedEvents\Controller\LinkedEventsImageController
   *   The controller.
   */
  private function createSut(array $responses, array &$history, ?int $requestTime = NULL): LinkedEventsImageController {
    $client = $this->createMockHistoryMiddlewareHttpClient($history, $responses);
    $this->container->set('http_client', $client);

    $time = $this->prophesize(TimeInterface::class);
    $time->getRequestTime()->willReturn($requestTime ?? time());

    return new LinkedEventsImageController(
      $this->container->get('entity_type.manager'),
      $this->container->get('http_client'),
      $this->container->get('cache.default'),
      $time->reveal(),
      $this->container->get(KillSwitch::class),
    );
  }

  /**
   * Gets the responses for a successful image fetch.
   *
   * @return array<int, \GuzzleHttp\Psr7\Response>
   *   The Linked Events API response and the image.
   */
  private function getSuccessResponses(): array {
    /** @var \stdClass $image */
    $image = array_first($this->getTestFiles('image'));

    return [
      new Psr7Response(body: (string) json_encode([
        'url' => self::LINKED_EVENTS_IMAGE_URL,
        'last_modified_time' => self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME,
      ])),
      new Psr7Response(body: (string) file_get_contents($image->uri)),
    ];
  }

  /**
   * Tests that arbitrary times don't fill the caches.
   */
  public function testArbitraryTime() : void {
    $request = fn (string $time) => new Request([
      'style' => self::SUPPORTED_IMAGE_STYLE,
      'time' => $time,
    ]);
    $killSwitch = $this->container->get(KillSwitch::class);

    $history = [];
    $response = $this->createSut($this->getSuccessResponses(), $history)
      ->deliver($request(self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME), '123');
    $this->assertInstanceOf(TrustedRedirectResponse::class, $response);
    $this->assertCount(2, $history);
    $this->assertNull($killSwitch->check(new Response(), new Request()));
    $url = $response->getTargetUrl();

    // Other times are served from the same cache entry without requests and
    // the responses are not stored in the page cache.
    $history = [];
    $response = $this->createSut([], $history, time() + 30)->deliver($request('random'), '123');
    $this->assertInstanceOf(TrustedRedirectResponse::class, $response);
    $this->assertSame($url, $response->getTargetUrl());
    $this->assertCount(0, $history);
    $this->assertSame(KillSwitch::DENY, $killSwitch->check(new Response(), new Request()));

    // The image is checked for updates once the interval has passed.
    $history = [];
    $response = $this->createSut([$this->getSuccessResponses()[0]], $history, time() + 61)->deliver($request('other'), '123');
    $this->assertInstanceOf(TrustedRedirectResponse::class, $response);
    $this->assertSame($url, $response->getTargetUrl());
    $this->assertCount(1, $history);
  }

  /**
   * Tests that connection errors are handled.
   */
  public function testConnectionError() : void {
    $history = [];
    $response = $this->createSut([
      new ConnectException('Timeout', new Psr7Request('GET', 'https://api.hel.fi/linkedevents/v1/image/123')),
    ], $history)->deliver(new Request([
      'style' => self::SUPPORTED_IMAGE_STYLE,
      'time' => self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME,
    ]), '123');

    $this->assertEquals(404, $response->getStatusCode());
  }

  /**
   * Call sut.
   *
   * @param string $image_id
   *   The image id.
   * @param string $image_style
   *   The image style.
   * @param string $time
   *   The time.
   * @param array<mixed>|null $apiResponse
   *   The API response.
   * @param bool $is_linked_events_api_success
   *   Whether the API response should succeed.
   * @param bool $is_download_external_success
   *   Whether the external image download should succeed.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  private function callSut(
    string $image_id = '123',
    string $image_style = self::SUPPORTED_IMAGE_STYLE,
    string $time = self::LINKED_EVENTS_IMAGE_LAST_MODIFIED_TIME,
    ?array $apiResponse = [],
    bool $is_linked_events_api_success = TRUE,
    bool $is_download_external_success = TRUE,
  ): Response {

    if (!$apiResponse) {
      $apiResponse = [
        'url' => self::LINKED_EVENTS_IMAGE_URL,
        'last_modified_time' => $time,
      ];
    }
    $responses = [];

    if ($is_linked_events_api_success) {
      $responses[] = new Psr7Response(body: (string) json_encode($apiResponse));

      if ($is_download_external_success) {
        $image = array_first($this->getTestFiles('image'));
        $responses[] = new Psr7Response(body: (string) file_get_contents($image->uri));
      }
      else {
        $responses[] = new RequestException(
          "Test failure",
          new Psr7Request('GET', 'https://localhost/image.png'),
          new Psr7Response(404),
        );
      }

    }
    else {
      $responses[] = new RequestException(
        "Test failure",
        new Psr7Request('GET', 'https://api.hel.fi/linkedevents/v1/image/123'),
        new Psr7Response(504),
      );
    }
    $container = [];
    $response = $this->createSut($responses, $container)->deliver(new Request([
      'style' => $image_style,
      'time' => $time,
    ]), $image_id);

    // Test last download url cache busting query parameter.
    if ($response instanceof TrustedRedirectResponse) {
      assert($container[1]['request'] instanceof Psr7Request);
      $this->assertStringContainsString('time=', (string) $container[1]['request']->getUri());
    }

    return $response;
  }

}
