<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Enum;

use Drupal\Core\StringTranslation\TranslatableMarkup;

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
      ServiceMapLink::CITYBIKE_STATIONS_STANDS => 'eRqwiU',
      ServiceMapLink::STREET_PARK_PROJECTS => 'eDBTcc',
      ServiceMapLink::PLANS_IN_PROCESS => 'eDB7Rk',
    };
  }

  /**
   * Gets the address label for a given service map link.
   *
   * @param string $address
   *   The address for which the label is generated.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The address label corresponding to the service map link.
   */
  public function getAddressLabel(string $address) : TranslatableMarkup {
    return match($this) {
      ServiceMapLink::ROADWORK_EVENTS => new TranslatableMarkup('Street works and events near the address @address', ['@address' => $address], ['context' => 'Helsinki near you address label']),
      ServiceMapLink::CITYBIKE_STATIONS_STANDS => new TranslatableMarkup('City bike stations and bicycle racks near the address @address', ['@address' => $address], ['context' => 'Helsinki near you address label']),
      ServiceMapLink::STREET_PARK_PROJECTS => new TranslatableMarkup('Street and park projects near the address @address', ['@address' => $address], ['context' => 'Helsinki near you address label']),
      ServiceMapLink::PLANS_IN_PROCESS => new TranslatableMarkup('Plans under preparation near the address @address', ['@address' => $address], ['context' => 'Helsinki near you address label']),
    };
  }

}
