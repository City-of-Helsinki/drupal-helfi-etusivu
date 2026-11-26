<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Statistics;

use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Figure;

/**
 * Fetches neighbourhood statistics from the Aluesarjat PxWeb API.
 */
interface StatisticsClientInterface {

  /**
   * Gets the preliminary population (ennakkoväkiluku) of an area.
   *
   * @param string $areaCode
   *   The area code, for example '0911101010'.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Figure
   *   The figure. Its value is NULL when the area has no data.
   *
   * @throws \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException
   *   Thrown when the API request fails.
   */
  public function getPopulation(string $areaCode) : Figure;

  /**
   * Gets the average state-taxable income of an area.
   *
   * @param string $areaCode
   *   The area code.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Figure
   *   The figure. Its value is NULL when the area has no data.
   *
   * @throws \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException
   *   Thrown when the API request fails.
   */
  public function getAverageIncome(string $areaCode) : Figure;

  /**
   * Gets the dwellings of an area by building type and completion year.
   *
   * @param string $areaCode
   *   The area code.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Figure
   *   The total, with a per-building-type breakdown. Each building type in
   *   turn carries a per-completion-year breakdown.
   *
   * @throws \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException
   *   Thrown when the API request fails.
   */
  public function getDwellings(string $areaCode) : Figure;

  /**
   * Gets the latest available value of a table's period variable.
   *
   * The tables are updated on their own schedules, so the current period is
   * read from table metadata instead of being hardcoded.
   *
   * @param string $table
   *   The table path, for example 'vrm/ennak/alu_ennak_001b.px'.
   * @param string $variable
   *   The period variable, for example 'Vuosi'.
   *
   * @return string
   *   The latest period.
   *
   * @throws \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException
   *   Thrown when the request fails or the variable is missing.
   */
  public function getLatestPeriod(string $table, string $variable) : string;

}
