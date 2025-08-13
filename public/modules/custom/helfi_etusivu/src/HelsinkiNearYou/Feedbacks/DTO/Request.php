<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\DTO;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * A DTO to represent feedback request.
 */
final readonly class Request {

  /**
   * Constructs a new instance.
   *
   * @param string $lat
   *   The latitude.
   * @param string $lon
   *   The longitude.
   * @param float $radius
   *   The radius.
   * @param int|null $limit
   *   The item limit.
   * @param \Drupal\Core\Datetime\DrupalDateTime|null $start_date
   *   The start date.
   */
  public function __construct(
    public string $lat,
    public string $lon,
    public float $radius,
    public ?int $limit = NULL,
    public ?DrupalDateTime $start_date = NULL,
  ) {
  }

}
