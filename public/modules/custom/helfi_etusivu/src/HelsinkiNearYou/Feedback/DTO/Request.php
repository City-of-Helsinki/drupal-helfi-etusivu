<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * A DTO to represent feedback request.
 */
final readonly class Request {

  /**
   * Constructs a new instance.
   *
   * @param float $lat
   *   The latitude.
   * @param float $lon
   *   The longitude.
   * @param float $radius
   *   The radius.
   * @param int|null $limit
   *   The item limit.
   * @param \Drupal\Core\Datetime\DrupalDateTime|null $start_date
   *   The start date.
   */
  public function __construct(
    public float $lat,
    public float $lon,
    public float $radius,
    public ?int $limit,
    public ?DrupalDateTime $start_date = NULL,
  ) {
  }

}
