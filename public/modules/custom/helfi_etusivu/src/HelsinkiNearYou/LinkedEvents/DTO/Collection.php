<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO;

/**
 * A DTO to store LinkedEvents items.
 */
final readonly class Collection {

  /**
   * Constructs a new instance.
   *
   * @param int $numItems
   *   The total number of items.
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO\Event[] $items
   *   An array of events.
   */
  public function __construct(
    public int $numItems,
    public array $items,
  ) {
  }

}
