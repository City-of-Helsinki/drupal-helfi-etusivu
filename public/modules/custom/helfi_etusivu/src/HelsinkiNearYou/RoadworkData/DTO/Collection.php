<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\DTO;

/**
 * A DTO to store Roadwork items.
 */
final readonly class Collection {

  public function __construct(
    public int $numItems,
    public array $items,
  ) {
  }

}
