<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\DTO;

/**
 * A DTO to store Feedback items.
 */
final readonly class Collection {

  public function __construct(
    public int $numItems,
    public array $items,
  ) {
  }

}
