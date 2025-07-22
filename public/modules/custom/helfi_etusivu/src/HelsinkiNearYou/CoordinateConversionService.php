<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou;

use proj4php\Point;
use proj4php\Proj;
use proj4php\Proj4php;

/**
 * Service for converting coordinates between different projections.
 */
class CoordinateConversionService {

  /**
   * The proj4php instance.
   *
   * @var \proj4php\Proj4php
   */
  protected Proj4php $proj4;

  /**
   * Source projection (WGS84).
   *
   * @var \proj4php\Proj
   */
  protected Proj $wgs84Projection;

  /**
   * Target projection (ETRS-GK25).
   *
   * @var \proj4php\Proj
   */
  protected Proj $etrsGk25Projection;

  /**
   * Constructs a new CoordinateConversionService object.
   */
  public function __construct() {
    // Initialize proj4php.
    $this->proj4 = new Proj4php();

    // Define the source projection (WGS84)
    $this->wgs84Projection = new Proj('EPSG:4326', $this->proj4);

    // Define the target projection (ETRS-GK25)
    $this->etrsGk25Projection = new Proj('EPSG:3879', $this->proj4);
  }

  /**
   * Convert coordinates from WGS84 to ETRS-GK25.
   *
   * @param float $latitude
   *   The latitude in WGS84 (EPSG:4326).
   * @param float $longitude
   *   The longitude in WGS84 (EPSG:4326).
   *
   * @return array|null
   *   An array with 'x' and 'y' keys for the converted coordinates in
   *   ETRS-GK25,
   *   or NULL if conversion failed.
   */
  public function wgs84ToEtrsGk25(float $latitude, float $longitude): ?array {
    try {
      // Create a point from the source coordinates with the source projection.
      $pointSrc = new Point($longitude, $latitude, $this->wgs84Projection);

      // Transform the point to the target projection.
      $pointDest = $this->proj4->transform($this->etrsGk25Projection, $pointSrc);

      return [
        'x' => $pointDest->x,
        'y' => $pointDest->y,
      ];
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Convert coordinates from ETRS-GK25 to WGS84.
   *
   * @param float $latitude
   *   The latitude in ETRS-GK25 (EPSG:3879).
   * @param float $longitude
   *   The longitude in ETRS-GK25 (EPSG:3879).
   *
   * @return array|null
   *   An array with 'x' and 'y' keys for the converted coordinates in
   *   WGS84,
   *   or NULL if conversion failed.
   */
  public function etrsGk25ToWgs84(float $latitude, float $longitude): ?array {
    try {
      // Create a point from the source coordinates with the source projection.
      $pointSrc = new Point($longitude, $latitude, $this->etrsGk25Projection);

      // Transform the point to the target projection.
      $pointDest = $this->proj4->transform($this->wgs84Projection, $pointSrc);

      return [
        'x' => $pointDest->x,
        'y' => $pointDest->y,
      ];
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}
