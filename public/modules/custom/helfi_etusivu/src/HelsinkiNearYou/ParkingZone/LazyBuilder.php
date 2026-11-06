<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\ParkingZone;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\ParkingZone;
use Drupal\helfi_api_base\ServiceMap\ResidentParkingZoneServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * A lazy builder for resident parking zone block.
 */
final readonly class LazyBuilder implements TrustedCallbackInterface {

  private const MAP_URL = 'https://kartta.hel.fi/ws/geoserver/avoindata/wms';

  private const MAP_BASE_LAYER = 'avoindata:Opaskartta_Helsinki';

  private const MAP_ZONE_LAYER = 'avoindata:Asukas_ja_yrityspysakointivyohykkeet_alue';

  /**
   * The map image size.
   *
   * Matches the 3:2 aspect ratio of card images, and is large enough for the
   * biggest derivative of the 'card' responsive image style.
   */
  private const MAP_WIDTH = 1200;

  private const MAP_HEIGHT = 800;

  public function __construct(
    private ResidentParkingZoneServiceInterface $residentParkingZoneService,
    #[Autowire(service: 'logger.channel.helfi_etusivu')]
    private LoggerInterface $logger,
  ) {
  }

  /**
   * A lazy-builder callback.
   *
   * @param \Drupal\helfi_api_base\ServiceMap\DTO\Address $address
   *   The address.
   * @param string $langcode
   *   The language code.
   *
   * @return array<string, mixed>
   *   The render array.
   */
  public function build(Address $address, string $langcode): array {
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
      '#theme' => 'helsinki_near_you_lazy_builder_content',
    ];

    try {
      $zone = $this->residentParkingZoneService->getParkingZone($address->location);
    }
    catch (\Exception $e) {
      Error::logException($this->logger, $e);
      return $build + ['#has_error' => TRUE];
    }

    $build['#content'] = [
      '#theme' => 'helsinki_near_you_parking_zone',
    ];

    if (!$zone) {
      return $build;
    }

    $build['#content'] += [
      '#name' => $zone->name,
      '#area_key' => $zone->areaKey,
      '#map_url' => $zone->mapUrl,
    ];

    if ($prices = ResidentParkingPrice::get($zone->areaKey)) {
      [$price, $electricPrice] = array_map(
        static fn (int|float $euros) => number_format($euros, is_int($euros) ? 0 : 2, $langcode === 'en' ? '.' : ',', ''),
        $prices,
      );
      $build['#content']['#price'] = new TranslatableMarkup(
        '€@price/month, fully electric vehicle €@electric_price/month',
        ['@price' => $price, '@electric_price' => $electricPrice],
        ['context' => 'Helsinki near you'],
      );
    }

    if ($mapImageUrl = $this->buildMapImageUrl($zone)) {
      $build['#content']['#map_image'] = [
        '#theme' => 'imagecache_external_responsive',
        '#uri' => $mapImageUrl,
        '#responsive_image_style_id' => 'card',
        // The card title already names the zone, so the map is decorative.
        '#alt' => '',
      ];
    }

    return $build;
  }

  /**
   * Builds a static map image URL showing the outline of the zone.
   *
   * The image is rendered by the kartta.hel.fi WMS service. The URL only
   * depends on the zone, so every address in a zone shares one cached image.
   *
   * @param \Drupal\helfi_api_base\ServiceMap\DTO\ParkingZone $zone
   *   The parking zone.
   *
   * @return string|null
   *   The image URL, or NULL when the zone cannot be drawn.
   */
  private function buildMapImageUrl(ParkingZone $zone): ?string {
    $bbox = array_map('floatval', explode(',', $zone->bbox));

    if (!$zone->areaKey || count($bbox) !== 4) {
      return NULL;
    }
    [$minLat, $minLon, $maxLat, $maxLon] = $bbox;

    // Widen the box to the image aspect ratio, so the map is not stretched.
    // A degree of longitude is shorter than a degree of latitude by the cosine
    // of the latitude.
    $lonScale = cos(deg2rad(($minLat + $maxLat) / 2));
    $width = ($maxLon - $minLon) * $lonScale;
    $height = $maxLat - $minLat;

    if ($width <= 0 || $height <= 0) {
      return NULL;
    }
    $ratio = self::MAP_WIDTH / self::MAP_HEIGHT;

    if ($width / $height < $ratio) {
      $lonPad = ($height * $ratio - $width) / $lonScale / 2;
      $minLon -= $lonPad;
      $maxLon += $lonPad;
    }
    else {
      $latPad = ($width / $ratio - $height) / 2;
      $minLat -= $latPad;
      $maxLat += $latPad;
    }

    return Url::fromUri(self::MAP_URL, [
      'query' => [
        'SERVICE' => 'WMS',
        'VERSION' => '1.3.0',
        'REQUEST' => 'GetMap',
        'FORMAT' => 'image/png',
        'WIDTH' => self::MAP_WIDTH,
        'HEIGHT' => self::MAP_HEIGHT,
        // WMS 1.3.0 uses latitude, longitude axis order for EPSG:4326.
        'CRS' => 'EPSG:4326',
        'BBOX' => implode(',', array_map(
          static fn (float $value) => round($value, 5),
          [$minLat, $minLon, $maxLat, $maxLon],
        )),
        'LAYERS' => implode(',', [self::MAP_BASE_LAYER, self::MAP_ZONE_LAYER]),
        // Draw the zone as an outline, since the default style of the zone
        // layer is an opaque fill that hides the streets.
        'STYLES' => ',line',
        // Filter applies per layer: everything from the base layer, only the
        // current zone from the zone layer.
        'CQL_FILTER' => sprintf("INCLUDE;asukaspysakointitunnus='%s'", str_replace("'", "''", $zone->areaKey)),
      ],
    ])->toString();
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() : array {
    return ['build'];
  }

}
