<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\ParkingZone;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Monthly resident parking permit prices by zone.
 *
 * No API provides the prices, so admins copy them from
 * https://www.hel.fi/fi/kaupunkiymparisto-ja-liikenne/pysakointi/asukaspysakointi
 * to the Helsinki near you settings form when the city changes them.
 *
 * The prices are in config_ignore.settings, so that changes made in
 * production are not reverted on deploy.
 *
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\Form\SettingsForm
 */
final readonly class ResidentParkingPrice {

  /**
   * The configuration name.
   */
  public const string CONFIG_NAME = 'helfi_etusivu.helsinki_near_you_settings';

  /**
   * The configuration key.
   */
  public const string CONFIG_KEY = 'resident_parking_prices';

  public function __construct(
    private ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Gets the prices of all zones.
   *
   * @return array<string, array{price: float, electric_price: float}>
   *   The prices in euros keyed by zone letter.
   */
  public function getAll(): array {
    return $this->configFactory->get(self::CONFIG_NAME)->get(self::CONFIG_KEY) ?? [];
  }

  /**
   * Gets the prices of a zone.
   *
   * @param string $areaKey
   *   The zone letter, for example "J".
   *
   * @return array{price: float, electric_price: float}|null
   *   The price and the fully electric vehicle price in euros, or NULL if not
   *   known.
   */
  public function get(string $areaKey): ?array {
    return $this->getAll()[$areaKey] ?? NULL;
  }

  /**
   * Replaces the prices of all zones.
   *
   * @param array<string, array{price: int|float|string, electric_price: int|float|string}> $prices
   *   The prices in euros keyed by zone letter.
   */
  public function setAll(array $prices): void {
    $stored = [];

    foreach ($prices as $areaKey => $price) {
      $stored[(string) $areaKey] = [
        'price' => (float) $price['price'],
        'electric_price' => (float) $price['electric_price'],
      ];
    }
    ksort($stored, SORT_NATURAL);

    $this->configFactory->getEditable(self::CONFIG_NAME)
      ->set(self::CONFIG_KEY, $stored)
      ->save();
  }

}
