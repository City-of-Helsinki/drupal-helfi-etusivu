<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\CoordinateConversionService;
use Drupal\helfi_etusivu\HelsinkiNearYou\Distance;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address;

/**
 * A lazy builder for Roadwork data block.
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
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address $address
   *   The address.
   *
   * @return array
   *   The render array.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function build(Address $address): array {
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    try {
      // Convert WGS84 coordinates to ETRS-GK25 (EPSG:3879) projection
      // which is required by the roadwork data service.
      $convertedCoords = $this->coordinateConversionService
        ->wgs84ToEtrsGk25($address->location->lat, $address->location->lon);

      if (!$convertedCoords) {
        throw new \InvalidArgumentException('Failed to convert coordinates to ETRS-GK25');
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

      foreach ($projects as $project) {
        $lat = $project->location->lat;
        $lon = $project->location->lon;

        $convertedProjectCoords = $this->coordinateConversionService
          ->etrsGk25ToWgs84($lat, $lon);

        if ($convertedProjectCoords) {
          $lat = $convertedProjectCoords['y'];
          $lon = $convertedProjectCoords['x'];
        }

        $build['items'][] = [
          '#theme' => 'helsinki_near_you_roadwork_item',
          '#title' => $project->title,
          '#uri' => $project->url,
          '#work_type' => $project->type,
          '#address' => $project->address,
          '#schedule' => $project->schedule,
          '#lat' => $lat,
          '#lon' => $lon,
          '#distance_label' => Distance::label(
            $address->location->lat,
            $address->location->lon,
            $lat,
            $lon,
          ),
        ];
      }
    }
    catch (\Exception) {
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() : array {
    return ['build'];
  }

}
