<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData;

use Drupal\Core\Url;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address;

/**
 * Defines the interface for roadwork data processing services.
 *
 * This interface provides methods for processing and formatting roadwork data
 * for display in the Helsinki Design System components. It handles data
 * transformation, date formatting, and URL generation.
 *
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataService
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataClientInterface
 */
interface RoadworkDataServiceInterface {

  /**
   * Gets formatted roadwork projects for display by coordinates.
   *
   * @param float $lat
   *   The latitude in WGS84 decimal degrees.
   * @param float $lon
   *   The longitude in WGS84 decimal degrees.
   * @param int $distance
   *   (optional) The search radius in meters. Defaults to 2000 meters.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\DTO\Item[]
   *   An array of formatted roadwork projects, each containing:
   *   - title: (string) The project title/description
   *   - location: (string) The project location/address
   *   - schedule: (string) Formatted date range
   *   - url: (string) URL for more information
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   Thrown when there is an error communicating with the API.
   */
  public function getFormattedProjectsByCoordinates(float $lat, float $lon, int $distance = 2000): array;

  /**
   * Gets the URL for the "See all roadworks" page.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address $address
   *   (optional) The address to include in the URL. If provided, it will be
   *   used to pre-fill the search field on the target page.
   * @param string $langcode
   *   The langcode to get address for.
   *
   * @return \Drupal\Core\Url
   *   A URL object for the roadworks overview page with optional address
   *   parameter. The URL will point to the route
   *   'helfi_etusivu.helsinki_near_you_roadworks'.
   */
  public function getSeeAllUrl(Address $address, string $langcode): Url;

}
