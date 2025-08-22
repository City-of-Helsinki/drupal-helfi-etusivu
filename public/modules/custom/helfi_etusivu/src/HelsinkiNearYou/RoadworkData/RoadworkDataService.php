<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Location;
use Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\DTO\Collection;
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
final class RoadworkDataService implements RoadworkDataServiceInterface {

  use StringTranslationTrait;

  public function __construct(private RoadworkDataClientInterface $roadworkDataClient) {
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedProjectsByCoordinates(float $lat, float $lon, int $distance = 1000, ?int $limit = NULL, int $page = 0): Collection {
    [
      'features' => $projects,
      'totalFeatures' => $numItems,
    ] = $this->roadworkDataClient->getProjectsByCoordinates($lat, $lon, $distance, $limit, $page);

    $formatted = [];

    foreach ($projects as $feature) {
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

      if (empty($feature['geometry']['coordinates'])) {
        continue;
      }

      if (!$location = $this->extractFirstCoordinate($feature['geometry'])) {
        continue;
      }

      $item = new Item(
        title: $props['osoite'] ?? (string) $this->t('Work site', [], ['context' => 'Roadworks default title']),
        date_string: $props['tyo_alkaa'],
        url: sprintf(
          'https://kartta.hel.fi/?setlanguage=fi&e=%.2f&n=%.2f&r=4&l=Karttasarja,HKRHankerek_Hanke_Rakkoht_tanavuonna_Internet&o=100,100&geom=POINT(%.2f%20%.2f)',
          $location->lon,
          $location->lat,
          $location->lon,
          $location->lat
        ),
        // Get the work type (Kaivuilmoitus or Aluevuokraus)
        type: $props['tyyppi'] ?? (string) $this->t('Work', [], ['context' => 'Roadworks type fallback']),
        // Just pass the location string, let the template handle the label.
        address: $props['osoite'] ?? (string) $this->t('Location unknown', [], ['context' => 'Roadworks location fallback']),
        // Convert schedule to a string to prevent Drupal from treating it as
        // a render array.
        schedule: $formattedStart . ($formattedEnd ? ' - ' . $formattedEnd : ''),
        location: $location,
      );
      $formatted[] = $item;
    }
    return new Collection($numItems, $formatted);
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
    return new Location((float) $points[1], (float) $points[0], $type);
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
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address $address
   *   The address to include in the URL. It will be
   *   used to pre-fill the search field on the target page.
   * @param string $langcode
   *   The langcode.
   *
   * @return \Drupal\Core\Url
   *   A URL object pointing to the roadworks overview page with optional
   *   address parameter.
   */
  public function getSeeAllUrl(Address $address, string $langcode): Url {
    $options = [
      'query' => ['q' => $address->streetName->getName($langcode)],
    ];
    return Url::fromRoute('helfi_etusivu.helsinki_near_you_roadworks', [], $options);
  }

}
