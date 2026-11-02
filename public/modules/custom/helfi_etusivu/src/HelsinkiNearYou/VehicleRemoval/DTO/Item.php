<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval\DTO;

/**
 * Data transfer object representing a vehicle removal item.
 */
final readonly class Item {

  public function __construct(
    public string $title,
    public string $address,
    public string $date_range,
    public string $map_url,
    public string $time_range,
  ) {
  }

}
