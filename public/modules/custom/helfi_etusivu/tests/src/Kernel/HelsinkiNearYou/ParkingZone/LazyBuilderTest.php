<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\ParkingZone;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_api_base\ServiceMap\DTO\ParkingZone;
use Drupal\helfi_api_base\ServiceMap\DTO\StreetName;
use Drupal\helfi_api_base\ServiceMap\ResidentParkingZoneServiceInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\ParkingZone\LazyBuilder;
use Drupal\helfi_etusivu\HelsinkiNearYou\ParkingZone\ResidentParkingPrice;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests resident parking zone lazy builder.
 */
#[CoversClass(LazyBuilder::class)]
#[CoversClass(ResidentParkingPrice::class)]
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
class LazyBuilderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_api_base',
    'diff',
    'helfi_etusivu',
    'system',
  ];

  /**
   * Builds the render array with the given parking zone service result.
   *
   * @param \Drupal\helfi_api_base\ServiceMap\DTO\ParkingZone|\Exception|null $result
   *   The zone to return, or an exception to throw.
   *
   * @return array<string, mixed>
   *   The render array.
   */
  private function build(ParkingZone|\Exception|null $result): array {
    $service = $this->createMock(ResidentParkingZoneServiceInterface::class);
    $method = $service->method('getParkingZone');
    $result instanceof \Exception ? $method->willThrowException($result) : $method->willReturn($result);

    $sut = new LazyBuilder($service, $this->container->get('logger.channel.helfi_etusivu'));

    return $sut->build(new Address(
      StreetName::createFromArray(['fi' => 'Kolmas linja 10']),
      new Location(60.1849, 24.9496, 'Point'),
    ));
  }

  /**
   * Parses the query of the map image URL.
   *
   * @param array<string, mixed> $build
   *   The render array.
   *
   * @return array<int|string, mixed>
   *   The query parameters.
   */
  private function getMapImageQuery(array $build): array {
    parse_str((string) parse_url($build['#content']['#map_image']['#uri'], PHP_URL_QUERY), $query);
    return $query;
  }

  /**
   * Tests the build() callback.
   */
  public function testBuild(): void {
    // A zone is shown as a card with a static map of the zone. Bounding boxes
    // both narrower and wider than the image are covered.
    foreach (['60.18,24.93,60.19,24.95', '60.18,24.90,60.19,25.00'] as $bbox) {
      $build = $this->build(new ParkingZone(
        name: 'Alppiharju',
        areaKey: 'J',
        mapUrl: 'https://palvelukartta.hel.fi/fi/area',
        bbox: $bbox,
      ));

      $this->assertEquals(['max-age' => 0], $build['#cache']);
      $this->assertEquals('helsinki_near_you_lazy_builder_content', $build['#theme']);
      $this->assertArrayNotHasKey('#has_error', $build);

      $content = $build['#content'];
      $this->assertEquals('helsinki_near_you_parking_zone', $content['#theme']);
      $this->assertEquals('Alppiharju', $content['#name']);
      $this->assertEquals('J', $content['#area_key']);
      $this->assertEquals('https://palvelukartta.hel.fi/fi/area', $content['#map_url']);
      $this->assertEquals('imagecache_external_responsive', $content['#map_image']['#theme']);
      $this->assertEquals('card', $content['#map_image']['#responsive_image_style_id']);
      $this->assertEquals('Map of resident parking zone J, Alppiharju', (string) $content['#map_image']['#attributes']['alt']);
      $this->assertStringStartsWith('https://kartta.hel.fi/ws/geoserver/avoindata/wms?', $content['#map_image']['#uri']);

      $query = $this->getMapImageQuery($build);
      $this->assertEquals('GetMap', $query['REQUEST']);
      $this->assertEquals("INCLUDE;asukaspysakointitunnus='J'", $query['CQL_FILTER']);
      $this->assertEquals(',line', $query['STYLES']);

      // The map covers the whole zone with the 3:2 aspect ratio of the image.
      [$minLat, $minLon, $maxLat, $maxLon] = array_map('floatval', explode(',', $query['BBOX']));
      [$zoneMinLat, $zoneMinLon, $zoneMaxLat, $zoneMaxLon] = array_map('floatval', explode(',', $bbox));
      $this->assertLessThanOrEqual($zoneMinLat, $minLat, $bbox);
      $this->assertLessThanOrEqual($zoneMinLon, $minLon, $bbox);
      $this->assertGreaterThanOrEqual($zoneMaxLat, $maxLat, $bbox);
      $this->assertGreaterThanOrEqual($zoneMaxLon, $maxLon, $bbox);

      $width = ($maxLon - $minLon) * cos(deg2rad(($minLat + $maxLat) / 2));
      $this->assertEqualsWithDelta(1.5, $width / ($maxLat - $minLat), 0.01, $bbox);
    }

    // A zone that cannot be drawn is shown without a map.
    foreach ([['', '60.18,24.93,60.19,24.95'], ['J', ''], ['J', '60.18,24.93,60.18,24.95']] as [$areaKey, $bbox]) {
      $build = $this->build(new ParkingZone(name: 'Alppiharju', areaKey: $areaKey, bbox: $bbox));
      $this->assertEquals('Alppiharju', $build['#content']['#name']);
      $this->assertArrayNotHasKey('#map_image', $build['#content'], "$areaKey $bbox");
    }

    // Prices are shown with a decimal comma, whole euros without decimals.
    $price = $this->build(new ParkingZone(name: 'Ullanlinna', areaKey: 'C'))['#content']['#price'];
    $this->assertInstanceOf(TranslatableMarkup::class, $price);
    $this->assertEquals('64,50 €/month, fully electric vehicle 48,40 €/month', (string) $price);

    $price = $this->build(new ParkingZone(name: 'Kallio', areaKey: 'J'))['#content']['#price'];
    $this->assertEquals('60 €/month, fully electric vehicle 45 €/month', (string) $price);

    // Zones without a known price are shown without one.
    $build = $this->build(new ParkingZone(name: 'Unknown', areaKey: 'X'));
    $this->assertArrayNotHasKey('#price', $build['#content']);

    // An address without a zone is shown without zone details.
    $build = $this->build(NULL);
    $this->assertArrayNotHasKey('#has_error', $build);
    $this->assertEquals(['#theme' => 'helsinki_near_you_parking_zone'], $build['#content']);

    // A failed lookup is shown as an error.
    $build = $this->build(new ConnectException('Timeout', new Request('GET', 'https://example.com')));
    $this->assertTrue($build['#has_error']);
    $this->assertArrayNotHasKey('#content', $build);
  }

}
