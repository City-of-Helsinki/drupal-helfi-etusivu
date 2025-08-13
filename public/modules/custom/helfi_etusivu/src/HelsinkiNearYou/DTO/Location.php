<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\DTO;

final readonly class Location {

  public function __construct(
    public string $lat,
    public string $lon,
    public string $type,
  ) {
  }

  public static function createFromArray(array $data) : self {
    if (!isset($data['coordinates'], $data['type'])) {
      throw new \InvalidArgumentException('Missing "coordinates" or "type".');
    }
    [$lon, $lat] = $data['coordinates'];

    return new self($data['type'], (string) $lat, (string) $lon);
  }

}
