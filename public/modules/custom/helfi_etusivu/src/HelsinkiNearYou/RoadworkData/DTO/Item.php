<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\DTO;

use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Location;

final readonly class Item {

  public function __construct(
    public string $title,
    public string $date_string,
    public string $url,
    public string $type,
    public string $address,
    public string $schedule,
    public Location $location,
  ) {
  }

}
