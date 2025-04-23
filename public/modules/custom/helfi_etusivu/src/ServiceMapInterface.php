<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu;

use Drupal\helfi_etusivu\Enum\ServiceMapLink;

/**
 * Interface for ServiceMap implementations.
 */
interface ServiceMapInterface {

  /**
   * Get coordinates from servicemap API.
   *
   * @param string $address
   *   The address.
   *
   * @return ?array
   *   The coordinates.
   */
  public function getAddressData(string $address): ?array;

  /**
   * Queries location data based on address.
   *
   * @param string $address
   *   Address to query against.
   * @param int $page_size
   *   Maximum number of results.
   *
   * @return array
   *   Array of results.
   */
  public function query(string $address, int $page_size = 1): array;

  /**
   * Generate link to servicemap view with predefined data visible.
   *
   * @param \Drupal\helfi_etusivu\Enum\ServiceMapLink $link
   *   Service map link option.
   * @param string $address
   *   Address param for the link.
   *
   * @return string
   *   The resulting link.
   */
  public function getLink(ServiceMapLink $link, string $address): string;

}
