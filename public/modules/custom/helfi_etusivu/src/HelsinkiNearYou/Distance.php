<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A class to calculate distance between two coordinate points.
 */
final readonly class Distance {

  /**
   * Calculates the distance between two coordinate points.
   *
   * @param float $latA
   *   Point A latitude.
   * @param float $lonA
   *   Point A longitude.
   * @param float $latB
   *   Point B latitude.
   * @param float $lonB
   *   Point B longitude.
   *
   * @return int
   *   Returns the distance in meters.
   */
  public static function calculateDistance(float $latA, float $lonA, float $latB, float $lonB): int {
    $rad = M_PI / 180;
    $radius = 6371000;

    $latA = $latA * $rad;
    $lonA = $lonA * $rad;
    $latB = $latB * $rad;
    $lonB = $lonB * $rad;

    $latDelta = $latB - $latA;
    $lonDelta = $lonB - $lonA;

    // Calculate distance using the Haversine formula.
    return (int) round(2 * $radius * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latA) * cos($latB) * pow(sin($lonDelta / 2), 2))), 0);
  }

  /**
   * Calculates the distance between two coordinate points.
   *
   * @param int $distance
   *   The distance.
   *
   * @return string
   *   Returns the distance in meters or kilometers.
   */
  public static function label(int $distance): string {
    $unit = 'm';

    if ($distance >= 1000) {
      $distance = round($distance / 1000, 1);
      $unit = 'km';
    }

    return (string) new TranslatableMarkup('@distance @unit', [
      '@distance' => $distance,
      '@unit' => $unit,
    ]);
  }

}
