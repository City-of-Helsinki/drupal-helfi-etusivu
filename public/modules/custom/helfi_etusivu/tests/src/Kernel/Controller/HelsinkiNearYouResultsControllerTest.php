<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\external_entities\Entity\Query\External\Query;
use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\ResultsController;
use Drupal\helfi_etusivu\HelsinkiNearYou\CoordinateConversionService;
use Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataServiceInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\ServiceMap;
use Drupal\helfi_etusivu\HelsinkiNearYou\ServiceMapInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Kernel test for HelsinkiNearYouController.
 *
 * @group helfi_etusivu
 */
class HelsinkiNearYouResultsControllerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
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
      $this->createMock(LinkedEvents::class),
      $this->roadworkDataService,
      new CoordinateConversionService(),
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
    $mockRequest = $this->createMock(Request::class);
    $queryWithoutArgs = new InputBag([]);
    $mockRequest->query = $queryWithoutArgs;
    $redirect = $this->controller->content($mockRequest);
    $this->assertInstanceOf(RedirectResponse::class, $redirect);

    $badAddress = 'Nonexistant street';
    $validAddress = 'Kalevankatu 2';
    $addressTranslations = new \stdClass();
    $addressTranslations->fi = 'Kalevankatu 2';

    // Should redirect when serviceMap instance returns NULL for address query.
    $queryWithBadArgs = new InputBag([
      'q' => $badAddress,
    ]);
    $mockRequest->query = $queryWithBadArgs;
    $this->serviceMap->expects(self::exactly(2))
      ->method('getAddressData')
      ->willReturnMap([
        [Xss::filter(urldecode($badAddress)), NULL],
        [Xss::filter(urldecode($validAddress)), [
          'address_translations' => $addressTranslations,
          'coordinates' => [60.171, 24.934],
        ],
        ],
      ]);
    $badArgRedirect = $this->controller->content($mockRequest);
    $this->assertInstanceOf(RedirectResponse::class, $badArgRedirect);

    // Should return build array when address checks out.
    $queryWithValidArgs = new InputBag([
      'q' => $validAddress,
    ]);
    $mockRequest->query = $queryWithValidArgs;
    $build = $this->controller->content($mockRequest);

    $this->assertIsArray($build);
    $this->assertEquals('helsinki_near_you_results_page', $build['#theme']);
    $eventSettings = $build['#attached']['drupalSettings']['helfi_events'];
    $this->assertEquals(
      'https://tapahtumat.hel.fi',
      $eventSettings['baseUrl']
    );
    $this->assertEquals(
      '/helsinki-near-you/events?address=Kalevankatu%202',
      $eventSettings['seeAllNearYouLink']
    );
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
    $serviceGroups = $this->controller->buildServiceGroups('Kalevankatu 2');
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
    };
  }

  /**
   * Tests the addressSuggestions method.
   */
  public function testAddressSuggestions() {
    $mockRequest = $this->createMock(Request::class);
    $query = new InputBag([
      'q' => 'Kalev',
    ]);
    $mockRequest->query = $query;

    $this->serviceMap->expects(self::once())
      ->method('query')
      ->willReturn(array_map(
        function ($name) {
          return (object) [
            'name' => (object) [
              'fi' => $name,
            ],
          ];
        },
        [
          'Kalevankatu 2',
          'Lönnrotinkatu 3',
          'ALeksanterinkatu 20',
        ]),
      );

    $addressSuggestions = $this->controller->addressSuggestions($mockRequest);
    $this->assertInstanceOf(JsonResponse::class, $addressSuggestions);
  }

  /**
   * Tests the roadworks api.
   */
  public function testRoadworksApi() : void {
    $mockRequest = $this->createMock(Request::class);
    $query = new InputBag([]);
    $mockRequest->query = $query;

    $response = $this->controller->roadworksApi($mockRequest);
    $data = json_decode($response->getContent(), TRUE);
    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals('No coordinates provided', $data['meta']['error']);

    // Make sure exception is caught and fallback data is provided.
    $this->roadworkDataService
      ->expects(self::once())
      ->method('getFormattedProjectsByCoordinates')
      ->willThrowException(new \Exception('Message'));

    $mockRequest->query->set('lat', 1.0);
    $mockRequest->query->set('lon', 1.0);

    $response = $this->controller->roadworksApi($mockRequest);
    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
  }

}
