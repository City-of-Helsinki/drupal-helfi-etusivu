<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Statistics;

use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\District;

/**
 * Resolves coordinates into a statistical district.
 */
interface DistrictResolverInterface {

  /**
   * Resolves the statistical district containing the given location.
   *
   * @param \Drupal\helfi_api_base\ServiceMap\DTO\Location $location
   *   The location, in WGS84.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\District|null
   *   The district, or NULL when the location is not inside any Helsinki
   *   statistical district.
   *
   * @throws \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException
   *   Thrown when the API request fails.
   */
  public function resolve(Location $location) : ?District;

}
