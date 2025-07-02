<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\RoadworkData;

/**
 * Defines the interface for roadwork data clients.
 *
 * This interface provides methods to fetch roadwork data from external APIs.
 * Implementations should handle communication, error handling, and basic
 * data transformation.
 *
 * @see \Drupal\helfi_etusivu\RoadworkData\RoadworkDataClient
 * @see \Drupal\helfi_etusivu\RoadworkData\RoadworkDataService
 */
interface RoadworkDataClientInterface {

  /**
   * Gets roadwork projects near the given coordinates.
   *
   * @param float $lat
   *   The latitude in WGS84 decimal degrees.
   * @param float $lon
   *   The longitude in WGS84 decimal degrees.
   * @param int $distance
   *   (optional) The search radius in meters. Defaults to 1000 meters.
   *
   * @return array
   *   An array of roadwork projects, each containing:
   *   - id: (string) The project identifier
   *   - properties: (array) Project metadata including:
   *     - tyon_tyyppi: (string) Type of work
   *     - tyon_kuvaus: (string) Description of work
   *     - osoite: (string) Address of the work site
   *     - tyo_alkaa: (string) Start date in ISO 8601 format
   *     - tyo_paattyy: (string) End date in ISO 8601 format
   *     - www: (string) URL for more information
   *   - geometry: (array) Geographic data
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   Thrown when there is an error communicating with the API.
   */
  public function getProjectsByCoordinates(float $lat, float $lon, int $distance = 1000): array;

  /**
   * Gets roadwork projects near the given address.
   *
   * @param string $address
   *   The address to search near (e.g., 'Mannerheimintie 5, Helsinki').
   * @param int $distance
   *   (optional) The search radius in meters. Defaults to 1000 meters.
   *
   * @return array
   *   An array of roadwork projects in the same format as
   *   getProjectsByCoordinates().
   *   Returns an empty array if the address cannot be geocoded.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   Thrown when there is an error communicating with the geocoding service
   *   or API.
   * @throws \InvalidArgumentException
   *   Thrown when the address is empty.
   */
  public function getProjectsByAddress(string $address, int $distance = 1000): array;

}
