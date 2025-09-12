<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData;

/**
 * Defines the interface for roadwork data clients.
 *
 * This interface provides methods to fetch roadwork data from external APIs.
 * Implementations should handle communication, error handling, and basic
 * data transformation.
 *
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataClient
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataService
 */
interface RoadworkDataClientInterface {

  /**
   * Gets roadwork projects near the given coordinates.
   *
   * @param float $x
   *   X coordinate in EPSG:3879.
   * @param float $y
   *   Y coordinate in EPSG:3879.
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
   *    Thrown when there is an error communicating with the API.
   */
  public function getProjectsByCoordinates(float $x, float $y, int $distance = 1000): array;

}
