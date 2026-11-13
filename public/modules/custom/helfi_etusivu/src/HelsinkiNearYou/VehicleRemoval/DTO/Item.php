<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval\DTO;

/**
 * Data transfer object representing a vehicle removal item.
 */
final readonly class Item {

  /**
   * The constructor.
   *
   * @param string $title
   *   The title for the card.
   * @param string $address
   *   The address for the mobilenote.
   * @param string $date_range
   *   Dates when the mobile note is active (for example 12.2.2026-15.2.2026).
   * @param string $map_url
   *   The url to the map.
   * @param string $time_range
   *   The time range when mobile note is active (for example 24h or 08-18)
   * @param ?string $additional_text
   *   More specific information about mobile note.
   */
  public function __construct(
    public string $title,
    public string $address,
    public string $date_range,
    public string $map_url,
    public string $time_range,
    public ?string $additional_text,
  ) {
  }

  /**
   * Return the items for render array.
   *
   * @return array<mixed>
   *   DTO values formatted for render array.
   */
  public function toArray(): array {
    return [
      '#title' => $this->title,
      '#address' => $this->address,
      '#date_range' => $this->date_range,
      '#map_url' => $this->map_url,
      '#time_range' => $this->time_range,
      '#additional_text' => $this->additional_text,
    ];
  }

}
