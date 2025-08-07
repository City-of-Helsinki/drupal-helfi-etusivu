<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\CoordinateConversionService;

/**
 * A lazy builder for Feedback block.
 */
final readonly class LazyBuilder implements TrustedCallbackInterface {

  public function __construct(
    private RoadworkDataServiceInterface $roadworkDataService,
    private CoordinateConversionService $coordinateConversionService,
  ) {
  }

  /**
   * A lazy-builder callback.
   *
   * @param float $lon
   *   The lon.
   * @param float $lat
   *   The lat.
   * @param string $address
   *   The address.
   *
   * @return array
   *   The render array.
   */
  public function build(
    float $lon,
    float $lat,
    string $address = '',
  ): array {

    try {
      // Convert WGS84 coordinates to ETRS-GK25 (EPSG:3879) projection
      // which is required by the roadwork data service.
      $convertedCoords = $this->coordinateConversionService->wgs84ToEtrsGk25($lat, $lon);

      if (!$convertedCoords) {
        throw new \RuntimeException('Failed to convert coordinates to ETRS-GK25');
      }

      // Fetch roadwork projects within 1km radius of the converted coordinates.
      // Note: Parameters are in ETRS-GK25 projection (EPSG:3879) where:
      // - First parameter is northing (y-coordinate)
      // - Second parameter is easting (x-coordinate)
      // - Third parameter is search radius in meters.
      $projects = $this->roadworkDataService->getFormattedProjectsByCoordinates(
      // Northing (y-coordinate)
        $convertedCoords['y'],
        // Easting (x-coordinate)
        $convertedCoords['x'],
        // 1000 meters = 1km radius.
        1000
      ) ?? [];

      foreach ($projects as &$project) {
        if (!isset($project['coordinates'])) {
          continue;
        }

        $convertedProjectCoords = $this->coordinateConversionService->etrsGk25ToWgs84(
          $project['coordinates'][0],
          $project['coordinates'][1],
        );

        if ($convertedProjectCoords) {
          $project['coordinates'] = [
            'lat' => $convertedProjectCoords['y'],
            'lon' => $convertedProjectCoords['x'],
          ];
        }
        else {
          unset($project['coordinates']);
        }
      }

      $title = $this->roadworkDataService->getSectionTitle();

      return [
        'title' => $title,
        'see_all_url' => $this->roadworkDataService->getSeeAllUrl($address)
          ->toString(),
        'projects' => $projects,
      ];

    }
    catch (\Exception) {
      // Return empty results structure on error to prevent page breakage
      // Use provided address for 'See all' link, or get from request if not
      // provided.
      return [
        'title' => $this->roadworkDataService->getSectionTitle(),
        'see_all_url' => $this->roadworkDataService->getSeeAllUrl($address)
          ->toString(),
        'projects' => [],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() : array {
    return ['build'];
  }

}
