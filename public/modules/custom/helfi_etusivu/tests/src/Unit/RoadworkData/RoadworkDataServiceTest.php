<?php

namespace Drupal\Tests\helfi_etusivu\Unit\RoadworkData;

use Drupal\helfi_etusivu\RoadworkData\RoadworkDataService;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests the RoadworkDataService class.
 *
 * @coversDefaultClass \Drupal\helfi_etusivu\RoadworkData\RoadworkDataService
 * @group helfi_etusivu
 * @group helfi_etusivu_unit
 */
class RoadworkDataServiceTest extends UnitTestCase {

  /**
   * The roadwork data client prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\helfi_etusivu\RoadworkData\RoadworkDataClientInterface>
   */
  protected ObjectProphecy $roadworkDataClient;

  /**
   * The logger factory prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Logger\LoggerChannelFactoryInterface>
   */
  protected ObjectProphecy $loggerFactory;

  /**
   * The roadwork data service.
   *
   * @var \Drupal\helfi_etusivu\RoadworkData\RoadworkDataService
   */
  protected $roadworkDataService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the logger factory.
    $logger = $this->createMock('Psr\Log\LoggerInterface');
    $logger_factory = $this->createMock('Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $logger_factory->expects($this->any())
      ->method('get')
      ->willReturn($logger);

    $this->roadworkDataClient = $this->createMock(RoadworkDataClient::class);
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);

    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn('en');
    $this->languageManager->method('getCurrentLanguage')
      ->willReturn($language);

    $container = new ContainerBuilder();
    $container->set('language_manager', $this->languageManager);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('logger.factory', $logger_factory);
    \Drupal::setContainer($container);

    $this->roadworkDataService = new RoadworkDataService(
      $this->roadworkDataClient,
      $this->languageManager
    );
  }

  /**
   * Tests formatting of projects.
   *
   * @covers ::formatProjects
   */
  public function testFormatProjects() {
    $sampleFeatures = [
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
    ];

    // Since formatProjects is protected, we need to test it through a public method.
    $this->roadworkDataClient->expects($this->once())
      ->method('getProjectsByAddress')
      ->with('Test Address')
      ->willReturn($sampleFeatures);

    $result = $this->roadworkDataService->getFormattedProjectsByAddress('Test Address');

    $this->assertCount(1, $result);
    // Should use tyon_kuvaus as title.
    $this->assertEquals('Test Description', $result[0]['title']);
    $this->assertStringContainsString('Test Street 1', $result[0]['location']);
    $this->assertStringContainsString('01.01.2025', $result[0]['schedule']);
    $this->assertStringContainsString('01.01.2026', $result[0]['schedule']);
    $this->assertEquals('http://example.com/test', $result[0]['url']);
  }

  /**
   * Tests section title generation.
   *
   * @covers ::getSectionTitle
   */
  public function testGetSectionTitle() {
    // Test with address.
    $title = $this->roadworkDataService->getSectionTitle('Testikatu 1');
    $this->assertInstanceOf(TranslatableMarkup::class, $title);
    $this->assertEquals('Katu- ja puistohankkeet', $title->getUntranslatedString());

    // Test without address (should be the same as with address)
    $title = $this->roadworkDataService->getSectionTitle();
    $this->assertInstanceOf(TranslatableMarkup::class, $title);
    $this->assertEquals('Katu- ja puistohankkeet', $title->getUntranslatedString());
  }

  /**
   * Tests "See all" URL generation.
   *
   * @covers ::getSeeAllUrl
   */
  public function testGetSeeAllUrl() {
    $url = $this->roadworkDataService->getSeeAllUrl('Testikatu 1');
    $this->assertInstanceOf(Url::class, $url);
    $this->assertEquals('helfi_etusivu.helsinki_near_you_roadworks', $url->getRouteName());

    // Check that the query parameter is set correctly.
    $this->assertEquals([], $url->getRouteParameters());

    // Test with empty address - should be the same as with address.
    $url = $this->roadworkDataService->getSeeAllUrl('');
    $this->assertEquals([], $url->getRouteParameters());
  }

  /**
   * Tests getFormattedProjectsByCoordinates with valid coordinates.
   *
   * @covers ::getFormattedProjectsByCoordinates
   */
  public function testGetFormattedProjectsByCoordinates() {
    $sampleFeatures = [
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
    ];

    $this->roadworkDataClient->expects($this->once())
      ->method('getProjectsByCoordinates')
      ->with(60.192059, 24.945831, 2000)
      ->willReturn($sampleFeatures);

    $result = $this->roadworkDataService->getFormattedProjectsByCoordinates(60.192059, 24.945831);

    $this->assertCount(1, $result);
    $this->assertEquals('Test Description', $result[0]['title']);
    $this->assertStringContainsString('Test Street 1', $result[0]['location']);
    // Check that the schedule contains both start and end dates.
    $this->assertStringContainsString('01.01.2025', $result[0]['schedule']);
    $this->assertStringContainsString('01.01.2026', $result[0]['schedule']);
    $this->assertEquals('http://example.com/test', $result[0]['url']);
  }

  /**
   * Tests getFormattedProjectsByAddress with valid address.
   *
   * @covers ::getFormattedProjectsByAddress
   */
  public function testGetFormattedProjectsByAddress() {
    $sampleFeatures = [
      [
        'id' => 'test.2',
        'properties' => [
          'tyon_tyyppi' => 'Test Type 2',
          'osoite' => 'Test Street 2',
          'tyo_alkaa' => '2025-02-01T00:00:00Z',
          'tyo_paattyy' => '2025-02-28T23:59:59Z',
          'lisatietolinkki' => 'http://example.com/test2',
        ],
        'geometry' => [
          'type' => 'Point',
          'coordinates' => [25496190, 6673588],
        ],
      ],
    ];

    $this->roadworkDataClient->expects($this->once())
      ->method('getProjectsByAddress')
      ->with('Test Address 123')
      ->willReturn($sampleFeatures);

    $result = $this->roadworkDataService->getFormattedProjectsByAddress('Test Address 123');

    $this->assertCount(1, $result);
    $this->assertEquals('Test Type 2', $result[0]['title']);
    // Check that the schedule contains both start and end dates.
    $this->assertStringContainsString('01.02.2025', $result[0]['schedule']);
    $this->assertStringContainsString('01.03.2025', $result[0]['schedule']);
    $this->assertEquals('http://example.com/test2', $result[0]['url']);
  }

  /**
   * Tests formatProjects with empty features array.
   *
   * @covers ::getFormattedProjectsByCoordinates
   */
  public function testFormatProjectsEmpty() {
    $this->roadworkDataClient->expects($this->once())
      ->method('getProjectsByCoordinates')
      ->with(0, 0, 2000)
      ->willReturn([]);

    $result = $this->roadworkDataService->getFormattedProjectsByCoordinates(0, 0);
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests formatProjects with minimal feature data.
   *
   * @covers ::getFormattedProjectsByAddress
   */
  public function testFormatProjectsMinimal() {
    $features = [
      [
        'properties' => [
          'tyon_tyyppi' => 'Minimal Type',
          'osoite' => 'Minimal Street',
        ],
      ],
    ];

    $this->roadworkDataClient->expects($this->once())
      ->method('getProjectsByAddress')
      ->with('Test Address')
      ->willReturn($features);

    $result = $this->roadworkDataService->getFormattedProjectsByAddress('Test Address');

    $this->assertCount(1, $result);
    $this->assertEquals('Minimal Type', $result[0]['title']);
    $this->assertStringContainsString('Minimal Street', $result[0]['location']);
    $this->assertStringContainsString('Ei tiedossa', $result[0]['schedule']);
    $this->assertEquals('https://www.hel.fi', $result[0]['url']);
  }

  /**
   * Tests date formatting through the public API.
   *
   * @covers ::getFormattedProjectsByAddress
   * @dataProvider dateFormatProvider
   */
  public function testDateFormatting($input, $expected) {
    $features = [
      [
        'properties' => [
          'tyon_tyyppi' => 'Date Test',
          'osoite' => 'Test Street',
          'tyo_alkaa' => $input,
          'tyo_paattyy' => $input,
        ],
      ],
    ];

    $this->roadworkDataClient->expects($this->once())
      ->method('getProjectsByAddress')
      ->with('Test Address')
      ->willReturn($features);

    $result = $this->roadworkDataService->getFormattedProjectsByAddress('Test Address');

    if ($input === '') {
      $this->assertStringContainsString('Ei tiedossa', $result[0]['schedule']);
    }
    elseif ($input === 'not-a-date') {
      $this->assertStringContainsString($input, $result[0]['schedule']);
    }
    else {
      // For valid dates, just check that the schedule contains the formatted date.
      $this->assertStringContainsString(date('d.m.Y', strtotime($input)), $result[0]['schedule']);
    }
  }

  /**
   * Data provider for date formatting tests.
   */
  public function dateFormatProvider(): array {
    return [
      'ISO date' => ['2025-12-31T23:59:59Z', '31.12.2025'],
      'Date only' => ['2025-01-01', '01.01.2025'],
      'Invalid date' => ['not-a-date', 'not-a-date'],
      'Empty string' => ['', ''],
    ];
  }

}
