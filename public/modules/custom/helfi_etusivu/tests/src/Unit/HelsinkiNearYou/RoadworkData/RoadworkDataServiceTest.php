<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\RoadworkData;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_api_base\ServiceMap\DTO\StreetName;
use Drupal\helfi_etusivu\HelsinkiNearYou\CoordinateConversionService;
use Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataClient;
use Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataService;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the RoadworkDataService class.
 *
 * @coversDefaultClass \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataService
 * @group helfi_etusivu
 * @group helfi_etusivu_unit
 */
class RoadworkDataServiceTest extends UnitTestCase {

  /**
   * The roadwork data client prophecy.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $roadworkDataClient;

  /**
   * The roadwork data service.
   *
   * @var \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataService
   */
  protected $roadworkDataService;

  /**
   * The language manager.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the logger factory.
    $logger = $this->createMock(LoggerChannelInterface::class);
    $this->roadworkDataClient = $this->createMock(RoadworkDataClient::class);
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);

    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn('en');
    $this->languageManager->method('getCurrentLanguage')
      ->willReturn($language);

    $container = new ContainerBuilder();
    $container->set('language_manager', $this->languageManager);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('logger.channel.helfi_etusivu', $logger);
    \Drupal::setContainer($container);

    $this->roadworkDataService = new RoadworkDataService(
      $this->roadworkDataClient,
      new CoordinateConversionService(),
    );
  }

  /**
   * Tests "See all" URL generation.
   *
   * @covers ::getSeeAllUrl
   */
  public function testGetSeeAllUrl() {
    $url = $this->roadworkDataService->getSeeAllUrl(
      new Address(
        StreetName::createFromArray(['fi' => 'Testikatu 1']),
        new Location(123, 123, 'Point'),
      ),
      'fi',
    );
    $this->assertInstanceOf(Url::class, $url);
    $this->assertEquals('helfi_etusivu.helsinki_near_you_roadworks', $url->getRouteName());

    // Check that the query parameter is set correctly.
    $this->assertEquals([], $url->getRouteParameters());
  }

  /**
   * Tests getFormattedProjectsByCoordinates with valid coordinates.
   *
   * @covers ::getFormattedProjectsByCoordinates
   * @covers ::formatDate
   * @covers \Drupal\helfi_etusivu\HelsinkiNearYou\CoordinateConversionService::wgs84ToEtrsGk25
   */
  public function testGetFormattedProjectsByCoordinates() {
    $sampleFeatures = [
      'totalFeatures' => 1,
      'features' => [
        [
          'id' => 'test.1',
          'properties' => [
            'tyon_tyyppi' => 'Test Type',
            'tyon_kuvaus' => 'Test Description',
            'osoite' => 'Test Street 1',
            'tyo_alkaa' => '2025-01-01T00:00:00Z',
            'tyo_paattyy' => '2025-12-31T23:59:59Z',
            'www' => 'http://example.com/test',
          ],
          'geometry' => [
            'type' => 'Point',
            'coordinates' => [25496190, 6673588],
          ],
        ],
      ],
    ];

    $this->roadworkDataClient->expects($this->once())
      ->method('getProjectsByCoordinates')
      ->with(
        // Precomputed conversion from WGS84 to EPSG:3879.
        $this->callback(static fn($x) => abs($x - 25496994.90) < 0.1),
        $this->callback(static fn($y) => abs($y - 6675472.09) < 0.1),
        2000
      )
      ->willReturn($sampleFeatures);

    $result = $this->roadworkDataService->getFormattedProjectsByCoordinates(60.192059, 24.945831, 2000);

    $this->assertCount(1, $result->items);
    $this->assertEquals('Test Street 1', $result->items[0]->title);
    $this->assertStringContainsString('Test Street 1', $result->items[0]->address);
    // Check that the schedule contains both start and end dates.
    $this->assertStringContainsString('01.01.2025', $result->items[0]->schedule);
    $this->assertStringContainsString('01.01.2026', $result->items[0]->schedule);
    $this->assertEquals('https://kartta.hel.fi/?setlanguage=fi&e=25496190.00&n=6673588.00&r=4&l=Karttasarja,HKRHankerek_Hanke_Rakkoht_tanavuonna_Internet,allu_kaivuilmoitukset_kaynnissa,allu_kaivuilmoitukset_tuleva&o=100,100&geom=POINT(25496190.00%206673588.00)', $result->items[0]->url);
  }

  /**
   * Tests pagination in getFormattedProjectsByCoordinates.
   *
   * @covers ::getFormattedProjectsByCoordinates
   * @covers ::extractFirstCoordinate
   * @covers \Drupal\helfi_etusivu\HelsinkiNearYou\CoordinateConversionService::wgs84ToEtrsGk25
   */
  public function testPagination() {
    $sampleFeatures = [
      'totalFeatures' => 3,
      'features' => [
        [
          'id' => 'test.1',
          'properties' => [
            'tyon_tyyppi' => 'Test Type',
            'tyon_kuvaus' => 'Test Description',
            'osoite' => 'Kansallismuseo',
            'tyo_alkaa' => '2025-01-01T00:00:00Z',
            'tyo_paattyy' => '2025-12-31T23:59:59Z',
            'www' => 'http://example.com/test',
          ],
          'geometry' => [
            'type' => 'Point',
            'coordinates' => [25496190, 6673588],
          ],
        ],
        [
          'id' => 'test.2',
          'properties' => [
            'tyon_tyyppi' => 'Test Type',
            'tyon_kuvaus' => 'Test Description',
            'osoite' => 'Kallion kirkko',
            'tyo_alkaa' => '2025-01-01T00:00:00Z',
            'tyo_paattyy' => '2025-12-31T23:59:59Z',
            'www' => 'http://example.com/test',
          ],
          'geometry' => [
            'type' => 'Point',
            'coordinates' => [25497188, 6674587],
          ],
        ],
        [
          'id' => 'test.3',
          'properties' => [
            'tyon_tyyppi' => 'Test Type',
            'tyon_kuvaus' => 'Test Description',
            'osoite' => 'Päärautatieasema',
            'tyo_alkaa' => '2025-01-01T00:00:00Z',
            'tyo_paattyy' => '2025-12-31T23:59:59Z',
            'www' => 'http://example.com/test',
          ],
          'geometry' => [
            'type' => 'Point',
            'coordinates' => [25496750, 6673114],
          ],
        ],
      ],
    ];

    // Test should pass regardless of the shuffle, since
    // getFormattedProjectsByCoordinates should sort the
    // array by distance to the given point.
    shuffle($sampleFeatures['features']);

    $this->roadworkDataClient->expects($this->once())
      ->method('getProjectsByCoordinates')
      ->with(
      // Precomputed conversion from WGS84 to EPSG:3879.
        $this->callback(static fn($x) => abs($x - 25496994.90) < 0.1),
        $this->callback(static fn($y) => abs($y - 6675472.09) < 0.1),
        2000
      )
      ->willReturn($sampleFeatures);

    // Aleksis kiven katu.
    $result = $this->roadworkDataService->getFormattedProjectsByCoordinates(60.192059, 24.945831, 2000, 2, 1);

    // Returns the furthest point, since we are
    // skipping the first two elements (page 2).
    $this->assertCount(1, $result->items);
    $this->assertEquals(3, $result->numItems);
    $this->assertEquals('Päärautatieasema', $result->items[0]->title);
  }

  /**
   * Tests formatProjects with empty features array.
   *
   * @covers ::getFormattedProjectsByCoordinates
   * @covers \Drupal\helfi_etusivu\HelsinkiNearYou\CoordinateConversionService::wgs84ToEtrsGk25
   */
  public function testFormatProjectsEmpty() {
    $this->roadworkDataClient->expects($this->once())
      ->method('getProjectsByCoordinates')
      ->with(
        // Precomputed conversion from WGS84 to EPSG:3879.
        $this->callback(static fn($x) => abs($x - 25496994.90) < 0.1),
        $this->callback(static fn($y) => abs($y - 6675472.09) < 0.1),
        2000
      )
      ->willReturn(['features' => [], 'totalFeatures' => 0]);

    $result = $this->roadworkDataService->getFormattedProjectsByCoordinates(60.192059, 24.945831, 2000);
    $this->assertIsArray($result->items);
    $this->assertEmpty($result->items);
  }

  /**
   * Tests date formatting through the public API.
   *
   * @covers ::getFormattedProjectsByCoordinates
   * @dataProvider dateFormatProvider
   */
  public function testDateFormatting($input, $expected) {
    $features = [
      'features' => [
        [
          'properties' => [
            'tyon_tyyppi' => 'Date Test',
            'osoite' => 'Test Street',
            'tyo_alkaa' => $input,
            'tyo_paattyy' => $input,
          ],
          'geometry' => ['coordinates' => [123, 123], 'type' => 'Point'],
        ],
      ],
      'totalFeatures' => 1,
    ];

    $this->roadworkDataClient->expects($this->once())
      ->method('getProjectsByCoordinates')
      ->willReturn($features);

    $result = $this->roadworkDataService->getFormattedProjectsByCoordinates(123, 123);

    $this->assertEquals($expected, $result->items[0]->schedule);
  }

  /**
   * Data provider for date formatting tests.
   */
  public function dateFormatProvider(): array {
    return [
      'ISO date' => ['2025-12-31T23:59:59', '31.12.2025 - 31.12.2025'],
      'Date only' => ['2025-01-01', '01.01.2025 - 01.01.2025'],
      'Invalid date' => ['not-a-date', 'not-a-date - not-a-date'],
      'Empty string' => ['', 'Unknown - Ongoing'],
    ];
  }

}
