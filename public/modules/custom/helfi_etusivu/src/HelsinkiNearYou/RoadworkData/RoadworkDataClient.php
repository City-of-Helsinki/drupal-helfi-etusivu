<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\ServiceMapInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Fetches and processes roadwork data from the Helsinki Open Data API.
 *
 * This service handles communication with the external roadwork data API,
 * including making HTTP requests, error handling, and basic data
 * transformation.
 * It implements RoadworkDataClientInterface to ensure consistent API.
 *
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataClientInterface
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataService
 */
class RoadworkDataClient implements RoadworkDataClientInterface {

  /**
   * Constructs a new RoadworkDataClient instance.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\ServiceMapInterface $serviceMap
   *   The Servicemap service.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    #[Autowire(service: 'logger.channel.helfi_etusivu')] protected LoggerChannelInterface $logger,
    protected ServiceMapInterface $serviceMap,
  ) {
  }

  /**
   * Fetches roadwork projects near the given coordinates.
   *
   * Retrieves active roadwork projects from the Helsinki Open Data WFS service
   * within the specified distance of the given coordinates. Coordinates should
   * be in WGS84 (EPSG:4326) format.
   *
   * @param float $lat
   *   The latitude in WGS84 decimal degrees.
   * @param float $lon
   *   The longitude in WGS84 decimal degrees.
   * @param int $distance
   *   (optional) Search radius in meters. Defaults to 1000m.
   *
   * @return array
   *   An array of GeoJSON features containing roadwork project data.
   *   Returns an empty array on error or if no projects are found.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   If there is an error communicating with the API.
   */
  public function getProjectsByCoordinates(float $lat, float $lon, int $distance = 1000): array {
    try {
      // Format the current date in YYYY-MM-DD format for the API filter.
      $currentDate = (new \DateTime())->format('Y-m-d');

      // The API expects coordinates in EPSG:3879 (ETRS-GK25)
      // We assume the coordinates are already converted to EPSG:3879 before
      // being passed to this method.
      // In EPSG:3879, this is the easting (x-coordinate)
      $x = $lon;
      // In EPSG:3879, this is the northing (y-coordinate)
      $y = $lat;

      // Build the WFS request URL.
      $baseUrl = 'https://kartta.hel.fi/ws/geoserver/avoindata/wfs';
      $query = [
        'service' => 'wfs',
        'version' => '2.0.0',
        'request' => 'GetFeature',
        'typeName' => 'avoindata:Kaivuilmoitus_alue',
        'CQL_FILTER' => sprintf(
          'tyo_paattyy>%s AND DWITHIN(singlegeom,SRID=3879;POINT(%f %f),%d,meters)',
          $currentDate,
          $x,
          $y,
          $distance
        ),
        'outputFormat' => 'application/json',
      ];

      $response = $this->httpClient->request('GET', $baseUrl, [
        'query' => $query,
        'timeout' => 30,
      ]);

      $responseBody = (string) $response->getBody();
      $data = json_decode($responseBody, TRUE);

      if (!isset($data['features']) || !is_array($data['features'])) {
        $errorMsg = 'Invalid response format from roadworks API';
        if (isset($data['error'])) {
          $errorMsg .= ': ' . print_r($data['error'], TRUE);
        }
        elseif (isset($data['message'])) {
          $errorMsg .= ': ' . $data['message'];
        }
        $this->logger->error($errorMsg);

        return [];
      }

      return $data['features'];

    }
    catch (\Exception $e) {
      $errorMsg = 'Error fetching roadworks data: ' . $e->getMessage();
      $this->logger->error($errorMsg);

      return [];
    }
  }

  /**
   * Fetches roadwork projects near the given address.
   *
   * First geocodes the address to coordinates, then retrieves nearby
   * roadwork projects using those coordinates.
   *
   * @param string $address
   *   The address to search near (e.g., 'Mannerheimintie 5, Helsinki').
   * @param int $distance
   *   (optional) Search radius in meters. Defaults to 1000m.
   *
   * @return array
   *   An array of GeoJSON features containing roadwork project data.
   *   Returns an empty array if the address cannot be geocoded or no
   *   projects are found.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   If there is an error communicating with the geocoding service or API.
   * @throws \InvalidArgumentException
   *   If the address parameter is empty.
   */
  public function getProjectsByAddress(string $address, int $distance = 1000): array {
    try {
      // First, geocode the address to get coordinates.
      $geocoded = $this->geocodeAddress($address);

      if (!$geocoded) {
        $this->logger->warning('Could not geocode address: @address', ['@address' => $address]);
        return [];
      }

      // Get roadwork data for the geocoded coordinates.
      return $this->getProjectsByCoordinates(
        $geocoded['y'],
        $geocoded['x'],
        $distance
      );

    }
    catch (\Exception $e) {
      $this->logger->error('Error in getProjectsByAddress: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Converts an address to geographic coordinates using the Servicemap service.
   *
   * @param string $address
   *   The address to geocode (e.g., 'Mannerheimintie 5, Helsinki').
   *
   * @return array|null
   *   An associative array with 'x' (longitude) and 'y' (latitude) keys,
   *   or NULL if the address could not be geocoded.
   *
   * @throws \InvalidArgumentException
   *   If the address parameter is empty.
   */
  protected function geocodeAddress(string $address): ?array {
    try {
      $this->logger->debug('Geocoding address: @address', ['@address' => $address]);

      // Use Servicemap to geocode the address.
      $geocoded = $this->serviceMap->getAddressData($address);

      if (!$geocoded || !isset($geocoded['x']) || !isset($geocoded['y'])) {
        $this->logger->warning('Could not geocode address: @address', ['@address' => $address]);
        return NULL;
      }

      return [
        'x' => $geocoded['x'],
        'y' => $geocoded['y'],
      ];

    }
    catch (\Exception $e) {
      $this->logger->error('Error geocoding address @address: @error', [
        '@address' => $address,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
