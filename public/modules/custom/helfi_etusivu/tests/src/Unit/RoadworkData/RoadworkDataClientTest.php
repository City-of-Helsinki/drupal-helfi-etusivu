<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\RoadworkData;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\helfi_etusivu\RoadworkData\RoadworkDataClient;
use Drupal\helfi_etusivu\ServiceMapInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests the RoadworkDataClient class.
 *
 * @coversDefaultClass \Drupal\helfi_etusivu\RoadworkData\RoadworkDataClient
 * @group helfi_etusivu
 * @group helfi_etusivu_unit
 */
class RoadworkDataClientTest extends UnitTestCase {

  /**
   * The HTTP client prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\GuzzleHttp\ClientInterface>
   */
  protected ObjectProphecy $httpClient;

  /**
   * The logger channel prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Logger\LoggerChannelInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * The logger factory prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Logger\LoggerChannelFactoryInterface>
   */
  protected ObjectProphecy $loggerFactory;

  /**
   * The Servicemap service prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\helfi_etusivu\ServiceMapInterface>
   */
  protected ObjectProphecy $servicemap;

  /**
   * The RoadworkDataClient instance.
   *
   * @var \Drupal\helfi_etusivu\RoadworkData\RoadworkDataClient
   */
  protected RoadworkDataClient $roadworkDataClient;

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   *
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up string translation.
    $string_translation = $this->createMock(TranslationInterface::class);
    $string_translation->method('translateString')
      ->willReturnCallback(function ($string) {
        return $string->getUntranslatedString();
      });

    // Set up the logger factory mock.
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method('get')
      ->willReturn($this->createMock(LoggerChannelInterface::class));

    // Set up the HTTP client mock.
    $this->httpClient = $this->createMock(ClientInterface::class);

    // Set up the Servicemap mock.
    $this->servicemap = $this->createMock(ServiceMapInterface::class);

    // Create the RoadworkDataClient instance.
    $this->roadworkDataClient = new RoadworkDataClient(
      $this->httpClient,
      $this->loggerFactory,
      $this->servicemap
    );

    // Set up container.
    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $string_translation);
    \Drupal::setContainer($this->container);
  }

  /**
   * Tests successful project retrieval.
   *
   * @covers ::getProjectsByCoordinates
   */
  public function testGetProjectsByCoordinatesSuccess() {
    // Mock successful API response.
    $response = new Response(200, [], json_encode([
      'type' => 'FeatureCollection',
      'features' => [
        [
          'id' => 'test.1',
          'properties' => [
            'tyon_tyyppi' => 'Test Type',
            'osoite' => 'Test Address',
            'tyo_alkaa' => date('Y-m-d\TH:i:s\Z', strtotime('+1 day')),
            'tyo_paattyy' => date('Y-m-d\TH:i:s\Z', strtotime('+2 days')),
            'linkki' => 'http://example.com/test',
          ],
          'geometry' => [
            'coordinates' => [24.945831, 60.192059],
          ],
        ],
      ],
    ]));

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', $this->stringContains('https://kartta.hel.fi/ws/geoserver/avoindata/wfs'))
      ->willReturn($response);

    $result = $this->roadworkDataClient->getProjectsByCoordinates(24.945831, 60.192059);
    $this->assertIsArray($result);
    $this->assertCount(1, $result);
    $this->assertEquals('test.1', $result[0]['id']);
  }

  /**
   * Tests error handling for API request.
   *
   * @covers ::getProjectsByCoordinates
   */
  public function testGetProjectsByCoordinatesApiError() {
    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException(new \Exception('API Error'));

    $result = $this->roadworkDataClient->getProjectsByCoordinates(60.192059, 24.945831);
    $this->assertEmpty($result);
  }

  /**
   * Tests handling of invalid response format.
   *
   * @covers ::getProjectsByCoordinates
   */
  public function testGetProjectsByCoordinatesInvalidResponse() {
    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn(new Response(200, [], '{"invalid": "response"}'));

    $result = $this->roadworkDataClient->getProjectsByCoordinates(60.192059, 24.945831);
    $this->assertEmpty($result);
  }

  /**
   * Tests getProjectsByAddress with successful geocoding.
   *
   * @covers ::getProjectsByAddress
   */
  public function testGetProjectsByAddressSuccess() {
    // Mock geocoding response.
    $this->servicemap->expects($this->once())
      ->method('getAddressData')
      ->with('Testikatu 1, 00100 Helsinki')
      ->willReturn([
        'x' => 24.945831,
        'y' => 60.192059,
      ]);

    // Mock successful API response for coordinates.
    $response = new Response(200, [], json_encode([
      'type' => 'FeatureCollection',
      'features' => [
        [
          'id' => 'test.1',
          'properties' => [
            'tyon_tyyppi' => 'Test Type',
            'osoite' => 'Test Address',
            'tyo_alkaa' => date('Y-m-d\TH:i:s\Z', strtotime('+1 day')),
            'tyo_paattyy' => date('Y-m-d\TH:i:s\Z', strtotime('+2 days')),
            'linkki' => 'http://example.com/test',
          ],
          'geometry' => [
            'coordinates' => [24.945831, 60.192059],
          ],
        ],
      ],
    ]));

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', $this->stringContains('https://kartta.hel.fi/ws/geoserver/avoindata/wfs'))
      ->willReturn($response);

    $result = $this->roadworkDataClient->getProjectsByAddress('Testikatu 1, 00100 Helsinki');
    $this->assertIsArray($result);
    $this->assertCount(1, $result);
    $this->assertEquals('test.1', $result[0]['id']);
  }

  /**
   * Tests getProjectsByAddress with geocoding failure.
   *
   * @covers ::getProjectsByAddress
   */
  public function testGetProjectsByAddressGeocodingFailure() {
    // Mock geocoding failure.
    $this->servicemap->expects($this->once())
      ->method('getAddressData')
      ->with('Nonexistent Address')
      ->willReturn(NULL);

    // Should not make any HTTP requests if geocoding fails.
    $this->httpClient->expects($this->never())
      ->method('request');

    $result = $this->roadworkDataClient->getProjectsByAddress('Nonexistent Address');
    $this->assertEmpty($result);
  }

}
