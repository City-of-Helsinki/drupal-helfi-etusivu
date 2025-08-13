<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\DTO;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Location;

final readonly class Item {

  public function __construct(
    public string             $title,
    public string             $url,
    public TranslatableMarkup $type,
    public string             $location,
    public TranslatableMarkup $location_label,
    public string             $schedule,
    public TranslatableMarkup $schedule_label,
    public Location           $geometry,
  ) {
  }

}
