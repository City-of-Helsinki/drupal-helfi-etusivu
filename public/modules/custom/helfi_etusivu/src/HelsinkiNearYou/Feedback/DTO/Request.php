<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO;

final readonly class Request {

  public function __construct(
    public float $lat,
    public float $lon,
    public float $radius,
    public string $locale,
  ) {
  }

}
