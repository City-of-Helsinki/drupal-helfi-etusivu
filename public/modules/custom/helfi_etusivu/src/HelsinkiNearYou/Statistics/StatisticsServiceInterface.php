<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Statistics;

use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Collection;

/**
 * Produces neighbourhood statistics for an address.
 */
interface StatisticsServiceInterface {

  /**
   * Gets the statistics of the district containing the given address.
   *
   * @param \Drupal\helfi_api_base\ServiceMap\DTO\Address $address
   *   The address.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Collection|null
   *   The statistics, or NULL when the address is not inside a Helsinki
   *   statistical district.
   *
   * @throws \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException
   *   Thrown when the district cannot be resolved.
   */
  public function getStatistics(Address $address) : ?Collection;

}
