<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval\DTO;

/**
 * Data transfer object representing a vehicle removal item.
 */
final readonly class MobileNoteResult {

  /**
   * The constructor.
   *
   * @param string|null $parkingZone
   *   Parking zone name.
   * @param array<string>|null $postalCodes
   *   All postal codes.
   * @param array<Item> $results
   *   An array of mobile note items.
   */
  public function __construct(
    public ?string $parkingZone,
    public ?array $postalCodes,
    public array $results,
  ) {
  }

}
