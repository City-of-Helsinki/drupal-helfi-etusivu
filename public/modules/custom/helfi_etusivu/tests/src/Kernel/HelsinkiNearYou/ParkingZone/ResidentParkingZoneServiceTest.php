<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\ParkingZone;

use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_etusivu\HelsinkiNearYou\ParkingZone\ResidentParkingZoneService;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * Tests the resident parking zone service.
 *
 * @coversDefaultClass \Drupal\helfi_etusivu\HelsinkiNearYou\ParkingZone\ResidentParkingZoneService
 * @group helfi_etusivu
 */
class ResidentParkingZoneServiceTest extends KernelTestBase {

  /**
   * Constructs the service with the given HTTP client.
   */
  private function createService(ClientInterface $httpClient): ResidentParkingZoneService {
    return new ResidentParkingZoneService(
      $httpClient,
      $this->container->get('language_manager'),
      $this->createMock(LoggerInterface::class),
    );
  }

  /**
   * A location inside a zone resolves to a populated DTO with an embed URL.
   *
   * @covers ::getParkingZone
   * @covers ::buildEmbedUrl
   * @covers ::boundaryToBbox
   * @covers ::__construct
   */
  public function testReturnsParkingZone(): void {
    $response = new Response(200, [], (string) json_encode([
      'count' => 1,
      'results' => [
        [
          'type' => 'resident_parking_zone',
          'name' => ['fi' => 'Kallio/Sörnäinen'],
          'boundary' => [
            'type' => 'MultiPolygon',
            'coordinates' => [[[[24.93, 60.17], [24.95, 60.19], [24.94, 60.18]]]],
          ],
        ],
      ],
    ]));

    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->expects($this->once())
      ->method('request')
      ->with('GET', $this->stringContains('administrative_division'))
      ->willReturn($response);

    $zone = $this->createService($httpClient)
      ->getParkingZone(new Location(60.18, 24.94, 'Point'));

    $this->assertNotNull($zone);
    $this->assertSame('Kallio/Sörnäinen', $zone->name);
    $this->assertStringContainsString('palvelukartta.hel.fi', $zone->embedUrl);
    $this->assertStringContainsString('/embed/area', $zone->embedUrl);
    $this->assertStringContainsString('selected=resident_parking_zone', $zone->embedUrl);
    $this->assertStringContainsString('bbox=', $zone->embedUrl);
  }

  /**
   * A zone without boundary geometry yields an embed URL with no bbox.
   *
   * @covers ::getParkingZone
   * @covers ::buildEmbedUrl
   * @covers ::boundaryToBbox
   */
  public function testReturnsParkingZoneWithoutBoundary(): void {
    $response = new Response(200, [], (string) json_encode([
      'count' => 1,
      'results' => [
        ['type' => 'resident_parking_zone', 'name' => ['fi' => 'Kamppi']],
      ],
    ]));
    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->method('request')->willReturn($response);

    $zone = $this->createService($httpClient)
      ->getParkingZone(new Location(60.16, 24.93, 'Point'));

    $this->assertNotNull($zone);
    $this->assertSame('Kamppi', $zone->name);
    $this->assertStringContainsString('/embed/area', $zone->embedUrl);
    $this->assertStringNotContainsString('bbox=', $zone->embedUrl);
  }

  /**
   * A location outside every zone resolves to NULL.
   *
   * @covers ::getParkingZone
   */
  public function testReturnsNullWhenNoZone(): void {
    $response = new Response(200, [], (string) json_encode(['count' => 0, 'results' => []]));
    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->method('request')->willReturn($response);

    $zone = $this->createService($httpClient)
      ->getParkingZone(new Location(60.0, 24.0, 'Point'));

    $this->assertNull($zone);
  }

  /**
   * A failed API request resolves to NULL instead of bubbling the exception.
   *
   * @covers ::getParkingZone
   */
  public function testReturnsNullOnRequestFailure(): void {
    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->method('request')->willThrowException(
      new ConnectException('Timeout', new Request('GET', 'https://api.hel.fi')),
    );

    $zone = $this->createService($httpClient)
      ->getParkingZone(new Location(60.18, 24.94, 'Point'));

    $this->assertNull($zone);
  }

}
