<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO;

/**
 * A DTO to store the statistics of one district.
 */
final readonly class Collection {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\District $district
   *   The district the figures describe.
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Figure[] $figures
   *   The figures, keyed by machine name.
   */
  public function __construct(
    public District $district,
    public array $figures,
  ) {
  }

}
