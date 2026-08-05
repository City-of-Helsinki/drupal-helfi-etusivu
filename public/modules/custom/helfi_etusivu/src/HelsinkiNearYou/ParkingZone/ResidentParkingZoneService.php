<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\ParkingZone;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_etusivu\HelsinkiNearYou\ParkingZone\DTO\ParkingZone;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Resolves resident parking zones from the Servicemap API.
 *
 * Resident parking zones are administrative divisions in the Servicemap
 * backend (the same API that powers Palvelukartta), separate from the TPR
 * service point registry.
 */
final class ResidentParkingZoneService implements ResidentParkingZoneServiceInterface {

  private const API_URL = 'https://api.hel.fi/servicemap/v2/administrative_division/';

  private const EMBED_BASE_URL = 'https://palvelukartta.hel.fi';

  private const DIVISION_TYPE = 'resident_parking_zone';

  /**
   * Languages supported by the Palvelukartta embed.
   */
  private const EMBED_LANGUAGES = ['fi', 'sv', 'en'];

  /**
   * Fraction of the zone's size to pad the map bounding box with.
   */
  private const BBOX_PADDING = 0.03;

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly LanguageManagerInterface $languageManager,
    #[Autowire(service: 'logger.channel.helfi_etusivu')]
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getParkingZone(Location $location): ?ParkingZone {
    try {
      $response = $this->httpClient->request('GET', self::API_URL, [
        RequestOptions::QUERY => [
          'type' => self::DIVISION_TYPE,
          'lat' => $location->lat,
          'lon' => $location->lon,
          'geometry' => 'true',
        ],
        RequestOptions::TIMEOUT => 10,
      ]);
    }
    catch (GuzzleException $e) {
      Error::logException($this->logger, $e);
      return NULL;
    }

    $result = json_decode((string) $response->getBody(), TRUE)['results'][0] ?? NULL;
    if (!$result) {
      return NULL;
    }

    return new ParkingZone(
      // The Servicemap API only provides the zone name in Finnish.
      name: (string) ($result['name']['fi'] ?? ''),
      embedUrl: $this->buildEmbedUrl($location, $result['boundary'] ?? NULL),
    );
  }

  /**
   * Builds the Palvelukartta embed URL, framed to the zone when possible.
   *
   * @param \Drupal\helfi_api_base\ServiceMap\DTO\Location $location
   *   The searched location.
   * @param array<string, mixed>|null $boundary
   *   The zone's GeoJSON geometry, or NULL when unavailable.
   *
   * @return string
   *   The Palvelukartta embed URL.
   */
  private function buildEmbedUrl(Location $location, ?array $boundary): string {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $language = in_array($langcode, self::EMBED_LANGUAGES, TRUE) ? $langcode : 'fi';

    $query = [
      'selected' => self::DIVISION_TYPE,
      'lat' => $location->lat,
      'lng' => $location->lon,
      'map' => 'servicemap',
    ];
    if ($bbox = $this->boundaryToBbox($boundary)) {
      $query['bbox'] = $bbox;
    }

    return Url::fromUri(
      sprintf('%s/%s/embed/area', self::EMBED_BASE_URL, $language),
      ['query' => $query],
    )->toString();
  }

  /**
   * Computes a padded bounding box from a GeoJSON geometry.
   *
   * @param array<string, mixed>|null $boundary
   *   A GeoJSON geometry with a 'coordinates' member.
   *
   * @return string|null
   *   Bounding box as "minLat,minLng,maxLat,maxLng", or NULL when the geometry
   *   has no usable coordinates.
   */
  private function boundaryToBbox(?array $boundary): ?string {
    if (!is_array($boundary['coordinates'] ?? NULL)) {
      return NULL;
    }

    $lons = $lats = [];
    $collect = function (array $node) use (&$collect, &$lons, &$lats): void {
      // A GeoJSON position is a numeric [lon, lat, ...] tuple; anything else is
      // a nested array of positions (ring / polygon / multipolygon).
      if (is_numeric($node[0] ?? NULL) && is_numeric($node[1] ?? NULL)) {
        $lons[] = (float) $node[0];
        $lats[] = (float) $node[1];
        return;
      }
      foreach ($node as $child) {
        if (is_array($child)) {
          $collect($child);
        }
      }
    };
    $collect($boundary['coordinates']);

    if (!$lons || !$lats) {
      return NULL;
    }

    // Pad so the zone is not flush against the map edges.
    $lonPad = (max($lons) - min($lons)) * self::BBOX_PADDING;
    $latPad = (max($lats) - min($lats)) * self::BBOX_PADDING;

    return implode(',', [
      min($lats) - $latPad,
      min($lons) - $lonPad,
      max($lats) + $latPad,
      max($lons) + $lonPad,
    ]);
  }

}
