<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\DTO;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * A DTO to represent feedback request.
 */
final readonly class Request {

  public function __construct(
    public float $lat,
    public float $lon,
    public float $radius,
    public ?int $limit = NULL,
    public int $offset = 0,
    public ?DrupalDateTime $start_date = NULL,
  ) {
  }

}
