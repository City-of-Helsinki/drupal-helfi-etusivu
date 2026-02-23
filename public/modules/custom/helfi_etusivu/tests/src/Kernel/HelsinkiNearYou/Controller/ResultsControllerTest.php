<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\external_entities\Entity\Query\External\Query;
use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\ResultsController;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_api_base\ServiceMap\DTO\StreetName;
use Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataServiceInterface;
use Drupal\helfi_api_base\ServiceMap\ServiceMap;
use Drupal\helfi_api_base\ServiceMap\ServiceMapInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Kernel test for HelsinkiNearYouController.
 *
 * @group helfi_etusivu
 */
class ResultsControllerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_api_base',
    'external_entities',
    'helfi_etusivu',
    'system',
  ];

  /**
   * The controller to test.
   */
  protected ResultsController $controller;

  /**
   * Mocked ServiceMap.
   */
  protected ServiceMap|MockObject $serviceMap;

  /**
   * The roadwork data service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected MockObject $roadworkDataService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->serviceMap = $this->createMock(ServiceMapInterface::class);
    $entityTypeManager = $this->createMock(EntityTypeManager::class);
    $this->roadworkDataService = $this->createMock(RoadworkDataServiceInterface::class);

    $this->controller = new ResultsController(
      $this->serviceMap,
      $this->roadworkDataService,
      $this->container->get(LanguageManagerInterface::class),
    );

    $mockEntityQuery = $this->createMock(Query::class);
    $mockEntityQuery
      ->method('range')
      ->willReturn($mockEntityQuery);
    $mockEntityQuery
      ->method('condition')
      ->willReturn(($mockEntityQuery));
    $mockEntityQuery
      ->method('accessCheck')
      ->willReturn($mockEntityQuery);
    $mockEntityQuery
      ->method('execute')
      ->willReturn([]);

    $mockEntityStorage = $this->createMock(EntityStorageInterface::class);
    $mockEntityStorage
      ->method('getQuery')
      ->willReturn($mockEntityQuery);
    $mockEntityStorage
      ->method('loadMultiple')
      ->willReturn([]);

    $entityTypeManager
      ->method('getStorage')
      ->willReturn($mockEntityStorage);
    $entityTypeManager
      ->method('getDefinitions')
      ->willReturn([]);

    $container = \Drupal::getContainer();
    $container->set('entity_type.manager', $entityTypeManager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the content() method.
   */
  public function testContent(): void {
    // Should redirect back when no address is supplied.
    $request = new Request();
    $redirect = $this->controller->content($request);
    $this->assertInstanceOf(RedirectResponse::class, $redirect);

    $badAddress = 'Nonexistant street';
    $validAddress = 'Kalevankatu 2';
    $addressTranslations = [
      'fi' => $validAddress,
    ];

    // Should redirect when serviceMap instance returns NULL for address query.
    $request->query = new InputBag([
      'q' => $badAddress,
    ]);
    $this->serviceMap->expects(self::exactly(2))
      ->method('getAddressData')
      ->willReturnMap([
        [Xss::filter(urldecode($badAddress)), NULL],
        [
          Xss::filter(urldecode($validAddress)),
          new Address(
            StreetName::createFromArray($addressTranslations),
            Location::createFromArray([
              'coordinates' => [60.171, 24.934],
              'type' => 'Point',
            ]),
          ),
        ],
      ]);
    $badArgRedirect = $this->controller->content($request);
    $this->assertInstanceOf(RedirectResponse::class, $badArgRedirect);

    // Should return build array when address checks out.
    $request->query = new InputBag([
      'q' => $validAddress,
    ]);
    $build = $this->controller->content($request);

    $this->assertIsArray($build);
    $this->assertEquals('helsinki_near_you_results_page', $build['#theme']);
    $this->assertArrayHasKey(
      'helfi_news_archive',
      $build['#attached']['drupalSettings']
    );
    $this->assertEquals(4, count($build['#service_groups']));
  }

  /**
   * Tests the buildServiceGroups method.
   */
  public function testBuildServiceGroups() {
    $serviceGroups = $this->controller->buildServiceGroups('Kalevankatu 2', 'fi');
    $this->assertEquals(count($serviceGroups), 4);

    foreach ($serviceGroups as $group) {
      $this->assertInstanceOf(TranslatableMarkup::class, $group['title']);
      $this->assertArrayHasKey('service_links', $group);
      $this->assertIsArray($group['service_links']);

      foreach ($group['service_links'] as $link) {
        $this->assertArrayHasKey('link_label', $link);
        $this->assertArrayHasKey('link_url', $link);
        $this->assertInstanceOf(TranslatableMarkup::class, $link['link_label']);
        $this->assertIsString($link['link_url']);
      }
    }
  }

}
