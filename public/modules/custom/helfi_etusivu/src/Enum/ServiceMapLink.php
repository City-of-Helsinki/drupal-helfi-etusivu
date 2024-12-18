<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Enum;

/**
 * Enum class for service map links.
 */
enum ServiceMapLink {
  case ROADWORK_EVENTS;
  case CITYBIKE_STATIONS_STANDS;
  case STREET_PARK_PROJECTS;
  case PLANS_IN_PROCESS;

  /**
   * Get link param for service map.
   *
   * @return string
   *   Link param.
   */
  public function link(): string {
    return match($this) {
      ServiceMapLink::ROADWORK_EVENTS => 'eDAB7W',
      ServiceMapLink::CITYBIKE_STATIONS_STANDS => 'eDFeCc',
      ServiceMapLink::STREET_PARK_PROJECTS => 'eDBTcc',
      ServiceMapLink::PLANS_IN_PROCESS => 'eDB7Rk',
    };
  }

}
