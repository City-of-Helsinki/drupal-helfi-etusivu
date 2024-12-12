<?php

declare(strict_types = 1);

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
   */
  public function link(): string {
    return match($this) {
      ServiceMapLink::ROADWORK_EVENTS => 'eCBuut',
      ServiceMapLink::CITYBIKE_STATIONS_STANDS => 'eCAduu',
      ServiceMapLink::STREET_PARK_PROJECTS => 'eCBJGT',
      ServiceMapLink::PLANS_IN_PROCESS => 'eCCv3K',
    };
  }
}
