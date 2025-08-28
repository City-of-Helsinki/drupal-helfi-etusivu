<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO;

/**
 * A DTO to store Feedback items.
 */
final readonly class Collection {

  /**
   * Constructs a new instance.
   *
   * @param int $numItems
   *   The total number of items.
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Feedback[] $items
   *   The feedback items.
   */
  public function __construct(
    public int $numItems,
    public array $items,
  ) {
  }

}
