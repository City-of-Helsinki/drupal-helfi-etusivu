<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\RoadworkData;

use Drupal\Core\Url;

/**
 * Defines the interface for roadwork data processing services.
 *
 * This interface provides methods for processing and formatting roadwork data
 * for display in the Helsinki Design System components. It handles data
 * transformation, date formatting, and URL generation.
 *
 * @see \Drupal\helfi_etusivu\RoadworkData\RoadworkDataService
 * @see \Drupal\helfi_etusivu\RoadworkData\RoadworkDataClientInterface
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
   * @return array
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
   * Gets formatted roadwork projects for display by address.
   *
   * @param string $address
   *   The address to search near (e.g., 'Mannerheimintie 5, Helsinki').
   * @param int $distance
   *   (optional) The search radius in meters. Defaults to 2000 meters.
   *
   * @return array
   *   An array of formatted roadwork projects in the same format as
   *   getFormattedProjectsByCoordinates(). Returns an empty array if the
   *   address
   *   cannot be geocoded.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   Thrown when there is an error communicating with the geocoding service
   *   or API.
   * @throws \InvalidArgumentException
   *   Thrown when the address is empty.
   */
  public function getFormattedProjectsByAddress(string $address, int $distance = 2000): array;

  /**
   * Gets the URL for the "See all roadworks" page.
   *
   * @param string $address
   *   (optional) The address to include in the URL. If provided, it will be
   *   used to pre-fill the search field on the target page.
   *
   * @return \Drupal\Core\Url
   *   A URL object for the roadworks overview page with optional address
   *   parameter. The URL will point to the route
   *   'helfi_etusivu.helsinki_near_you_roadworks'.
   */
  public function getSeeAllUrl(string $address = ''): Url;

  /**
   * Gets the section title for roadworks.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   A translatable string representing the section title for roadworks.
   *   This is typically used as a heading above the roadwork listings.
   */
  public function getSectionTitle();

}
