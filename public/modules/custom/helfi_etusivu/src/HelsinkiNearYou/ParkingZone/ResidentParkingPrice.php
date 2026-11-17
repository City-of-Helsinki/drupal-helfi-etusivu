<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\ParkingZone;

/**
 * Monthly resident parking permit prices by zone.
 *
 * No API provides the prices, so they are copied from
 * https://www.hel.fi/fi/kaupunkiymparisto-ja-liikenne/pysakointi/asukaspysakointi
 * and must be updated by hand when the city changes them.
 */
final class ResidentParkingPrice {

  /**
   * Prices in euros by zone letter: [price, fully electric vehicle price].
   */
  private const PRICES = [
    'A' => [60, 45],
    'B' => [64.5, 48.4],
    'C' => [64.5, 48.4],
    'D' => [64.5, 48.4],
    'DC' => [64.5, 48.4],
    'E' => [60, 45],
    'F' => [60, 45],
    'H' => [60, 45],
    'I' => [60, 45],
    'J' => [60, 45],
    'K' => [60, 45],
    'L' => [60, 45],
    'M' => [45, 37.5],
    'N' => [45, 37.5],
    'O' => [45, 37.5],
    'P' => [45, 37.5],
  ];

  /**
   * Gets the prices of a zone.
   *
   * @param string $areaKey
   *   The zone letter, for example "J".
   *
   * @return array{int|float, int|float}|null
   *   The price and the fully electric vehicle price, or NULL if not known.
   */
  public static function get(string $areaKey): ?array {
    return self::PRICES[$areaKey] ?? NULL;
  }

}
