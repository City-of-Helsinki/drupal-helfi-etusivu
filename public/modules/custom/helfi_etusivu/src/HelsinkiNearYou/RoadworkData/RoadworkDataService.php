<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Location;
use Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\DTO\Item;

/**
 * Processes and formats roadwork data for display.
 *
 * This service is responsible for transforming raw roadwork data from the API
 * into a format suitable for display in the Helsinki Design System components.
 * It handles date formatting, URL generation, and data structure
 * transformation.
 *
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataClientInterface
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\Controller\ResultsController
 */
class RoadworkDataService implements RoadworkDataServiceInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new RoadworkDataService instance.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataClientInterface $roadworkDataClient
   *   The roadwork data client.
   */
  public function __construct(protected RoadworkDataClientInterface $roadworkDataClient) {
  }

  /**
   * Retrieves and formats roadwork projects near the given coordinates.
   *
   * Fetches raw roadwork data for the specified location and transforms it into
   * a display-ready format with translated strings and formatted dates.
   *
   * @param float $lat
   *   The latitude in WGS84 decimal degrees.
   * @param float $lon
   *   The longitude in WGS84 decimal degrees.
   * @param int $distance
   *   (optional) Search radius in meters. Defaults to 1000m.
   *
   * @return array
   *   An array of formatted roadwork projects, each containing:
   *   - title: (string) The project title/description
   *   - location: (string) The project location/address
   *   - schedule: (string) Formatted date range
   *   - url: (string) URL for more information
   *   - type: (string) Type of work
   *   - raw_data: (array) Original project data
   */
  public function getFormattedProjectsByCoordinates(float $lat, float $lon, int $distance = 1000): array {
    $projects = $this->roadworkDataClient->getProjectsByCoordinates($lat, $lon, $distance);
    return $this->formatProjects($projects);
  }

  /**
   * Retrieves and formats roadwork projects near the given address.
   *
   * First geocodes the address to coordinates, then fetches and formats
   * nearby roadwork projects.
   *
   * @param string $address
   *   The address to search near (e.g., 'Mannerheimintie 5, Helsinki').
   * @param int $distance
   *   (optional) Search radius in meters. Defaults to 1000m.
   *
   * @return array
   *   An array of formatted roadwork projects in the same format as
   *   getFormattedProjectsByCoordinates(). Returns an empty array if the
   *   address cannot be geocoded.
   *
   * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataServiceInterface::getFormattedProjectsByAddress()
   */
  public function getFormattedProjectsByAddress(string $address, int $distance = 1000): array {
    $projects = $this->roadworkDataClient->getProjectsByAddress($address, $distance);
    return $this->formatProjects($projects);
  }

  /**
   * Transforms raw GeoJSON features into display-ready project data.
   *
   * Processes each feature to extract relevant information, format dates,
   * and create map URLs. Handles both single and multi-geometry features.
   *
   * @param array $features
   *   An array of GeoJSON features from the roadwork API, where each feature
   *   contains properties like:
   *   - properties: Array containing project metadata
   *   - geometry: GeoJSON geometry object with coordinates.
   *
   * @return array
   *   An array of formatted project data, sorted by start date (newest first).
   *   Each project includes translated labels and properly formatted dates.
   */
  protected function formatProjects(array $features): array {
    $formatted = [];

    foreach ($features as $feature) {
      if (!isset($feature['properties'])) {
        continue;
      }
      $props = $feature['properties'];

      // Use the text date fields for display.
      $startDateTxt = $props['tyo_alkaa_txt'] ?? '';
      $endDateTxt = $props['tyo_paattyy_txt'] ?? '';

      // Fallback to timestamp fields if text fields not available.
      $startDate = $startDateTxt ?: ($props['tyo_alkaa'] ?? '');
      $endDate = $endDateTxt ?: ($props['tyo_paattyy'] ?? '');

      // Format the dates.
      $formattedStart = $startDate ? $this->formatDate($startDate) : $this->t('Unknown', [], ['context' => 'Roadworks date fallback']);
      $formattedEnd = $endDate ? $this->formatDate($endDate) : $this->t('Ongoing', [], ['context' => 'Roadworks date fallback']);

      // Default to the Helsinki map URL.
      $url = 'https://kartta.hel.fi';

      $geometry = new Location('0', '0', 'Point');

      // If we have geometry, create a deep link to the map with coordinates.
      if (!empty($feature['geometry']['coordinates'])) {
        $geometry = $this->extractFirstCoordinate($feature['geometry']);

        if ($geometry) {
          $url = sprintf(
            'https://kartta.hel.fi/?setlanguage=fi&e=%.2f&n=%.2f&r=4&l=Karttasarja,HKRHankerek_Hanke_Rakkoht_tanavuonna_Internet&o=100,100&geom=POINT(%.2f%20%.2f)',
            $geometry->lon,
            $geometry->lat,
            $geometry->lon,
            $geometry->lat
          );
        }
      }

      $item = new Item(
        // This is now the address from osoite.
        title: $props['osoite'] ?? $this->t('Work site', [], ['context' => 'Roadworks default title']),
        // This now links to the map with coordinates.
        url: $url,
        // Get the work type (Kaivuilmoitus or Aluevuokraus)
        type: $props['tyyppi'] ?? $this->t('Work', [], ['context' => 'Roadworks type fallback']),
        // Just pass the location string, let the template handle the label.
        location: $props['osoite'] ?? $this->t('Location unknown', [], ['context' => 'Roadworks location fallback']),
        location_label: $this->t('Location', [], ['context' => 'Roadworks field label']),
        // Convert schedule to a string to prevent Drupal from treating it as
        // a render array.
        schedule: $formattedStart . ($formattedEnd ? ' - ' . $formattedEnd : ''),
        // Pass the translated label separately.
        schedule_label: $this->t('Schedule', [], ['context' => 'Roadworks field label']),
        geometry: $geometry,
      );
      $formatted[] = $item;
    }

    // Sort by start date (newest first)
    /*usort($formatted, function ($a, $b) {
      $aDate = $a['raw_data']['tyo_alkaa'] ?? '';
      $bDate = $b['raw_data']['tyo_alkaa'] ?? '';

      if ($aDate === $bDate) {
        return 0;
      }
      return ($aDate > $bDate) ? -1 : 1;
    });*/

    return $formatted;
  }

  /**
   * Extracts the first coordinate from a GeoJSON geometry.
   *
   * Handles different geometry types (Point, MultiPoint, LineString, etc.)
   * and returns the first coordinate pair found.
   *
   * @param array $geometry
   *   The GeoJSON geometry array.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Location|null
   *   The first [x, y] coordinate pair or null if not found.
   */
  protected function extractFirstCoordinate(array $geometry): ?Location {
    if (empty($geometry['coordinates'])) {
      return NULL;
    }
    $coords = $geometry['coordinates'];
    $type = $geometry['type'] ?? 'Point';

    $points = match (strtoupper($type)) {
      'POINT' => $coords,
      'MULTIPOINT', 'LINESTRING' => $coords[0] ?? NULL,
      'MULTILINESTRING', 'POLYGON' => $coords[0][0] ?? NULL,
      'MULTIPOLYGON' => $coords[0][0][0] ?? NULL,
      default => NULL,
    };

    if (!$points) {
      return NULL;
    }
    return new Location($points[1], $points[0], $type);
  }

  /**
   * Formats a date string for display.
   *
   * @param string $date_string
   *   The date string to format.
   *
   * @return string
   *   The formatted date.
   */
  protected function formatDate(string $date_string): string {
    // First try to parse as ISO 8601 date (YYYY-MM-DD)
    $date = \DateTime::createFromFormat('Y-m-d', $date_string);

    // If that fails, try to parse as a timestamp.
    if (!$date) {
      $timestamp = strtotime($date_string);
      if ($timestamp) {
        return date('d.m.Y', $timestamp);
      }
      // Return as-is if we can't parse it.
      return $date_string;
    }

    return $date->format('d.m.Y');
  }

  /**
   * Generates a URL to the full roadworks listing page.
   *
   * Creates a pre-filtered URL to the roadworks overview page, optionally
   * including the current search address as a query parameter.
   *
   * @param string $address
   *   (optional) The address to include in the URL. If provided, it will be
   *   used to pre-fill the search field on the target page.
   *
   * @return \Drupal\Core\Url
   *   A URL object pointing to the roadworks overview page with optional
   *   address parameter.
   */
  public function getSeeAllUrl(?Address $address = NULL): Url {
    // Create options array for the URL.
    $options = [];

    // Add query parameter if address is provided.
    if (!empty($address)) {
      $options['query'] = ['q' => $address->streetName->fi];
    }

    return Url::fromRoute('helfi_etusivu.helsinki_near_you_roadworks', [], $options);
  }

  /**
   * Returns the translated section title for roadwork listings.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   A translated string representing the section title for roadwork listings.
   *   This is typically used as a heading above roadwork project lists.
   */
  public function getSectionTitle() {
    return $this->t('Street and park projects');
  }

}
