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

  /**
   * A magic method to convert object to a string.
   *
   * Objects passed to lazy loader callback must be string-able in order
   * for them to work.
   *
   * @return string
   *   The location string.
   */
  public function __toString() : string {
    return $this->lat . ', ' . $this->lon;
  }

  public static function createFromArray(array $data) : self {
    if (!isset($data['coordinates'], $data['type'])) {
      throw new \InvalidArgumentException('Missing "coordinates" or "type".');
    }
    [$lon, $lat] = $data['coordinates'];

    return new self((string) $lat, (string) $lon, $data['type']);
  }

}
