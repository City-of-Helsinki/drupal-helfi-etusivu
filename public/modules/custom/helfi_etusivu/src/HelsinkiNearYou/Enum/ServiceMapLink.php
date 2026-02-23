<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Enum;

use Drupal\Component\Utility\Xss;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Enum class for service map links.
 */
enum ServiceMapLink {
  case RoadworkEvents;
  case CityBikeStationsStands;
  case StreetParkProjects;
  case PlansInProcess;

  /**
   * Get link param for service map.
   *
   * @return string
   *   Link param.
   */
  public function link(): string {
    return match($this) {
      ServiceMapLink::RoadworkEvents => 'eDAB7W',
      ServiceMapLink::CityBikeStationsStands => 'eRqwiU',
      ServiceMapLink::StreetParkProjects => 'eDBTcc',
      ServiceMapLink::PlansInProcess => 'eDB7Rk',
    };
  }

  /**
   * Gets the link for given service map type.
   *
   * @param string $address
   *   The address to get link for.
   * @param string $langcode
   *   The language to get link for.
   *
   * @return string
   *   The link.
   */
  public function getLink(string $address, string $langcode) :string {
    $query = [
      'addresslabel' => $this->getAddressLabel($address),
      'addresslocation' => Xss::filter($address),
      'link' => $this->link(),
      'setlanguage' => $langcode,
    ];

    return Url::fromUri(
      'https://kartta.hel.fi/',
      [
        'query' => $query,
      ],
    )->toString();
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
      ServiceMapLink::RoadworkEvents => new TranslatableMarkup('Street works and events near the address @address', ['@address' => $address], ['context' => 'Helsinki near you address label']),
      ServiceMapLink::CityBikeStationsStands => new TranslatableMarkup('City bike stations and bicycle racks near the address @address', ['@address' => $address], ['context' => 'Helsinki near you address label']),
      ServiceMapLink::StreetParkProjects => new TranslatableMarkup('Street and park projects near the address @address', ['@address' => $address], ['context' => 'Helsinki near you address label']),
      ServiceMapLink::PlansInProcess => new TranslatableMarkup('Plans under preparation near the address @address', ['@address' => $address], ['context' => 'Helsinki near you address label']),
    };
  }

}
