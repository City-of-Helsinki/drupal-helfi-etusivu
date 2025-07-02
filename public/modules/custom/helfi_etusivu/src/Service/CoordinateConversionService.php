<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Service;

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
  protected Proj $sourceProjection;

  /**
   * Target projection (ETRS-GK25).
   *
   * @var \proj4php\Proj
   */
  protected Proj $targetProjection;

  /**
   * Constructs a new CoordinateConversionService object.
   */
  public function __construct() {
    // Use Composer's autoloader to load the proj4php classes
    // The vendor directory is in the project root, one level up from
    // DRUPAL_ROOT.
    $autoloader = dirname(DRUPAL_ROOT) . '/vendor/autoload.php';

    if (!file_exists($autoloader)) {
      throw new \RuntimeException('Composer autoloader not found. Please run `composer install`.');
    }

    require_once $autoloader;

    // Initialize proj4php.
    $this->proj4 = new Proj4php();

    // Define the source projection (WGS84)
    $this->sourceProjection = new Proj('EPSG:4326', $this->proj4);

    // Define the target projection (ETRS-GK25)
    $this->targetProjection = new Proj('EPSG:3879', $this->proj4);
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
      $pointSrc = new Point($longitude, $latitude, $this->sourceProjection);

      // Transform the point to the target projection.
      $pointDest = $this->proj4->transform($this->targetProjection, $pointSrc);

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
