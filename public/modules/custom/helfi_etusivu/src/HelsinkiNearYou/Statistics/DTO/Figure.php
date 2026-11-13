<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A DTO to store one statistic.
 *
 * Values are numbers rather than formatted strings, and may be NULL: PxWeb
 * suppresses cells for small areas.
 */
final readonly class Figure {

  /**
   * Constructs a new instance.
   *
   * @param string $key
   *   Machine name, for example 'population'.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label. A plain string where it comes from the source data, such as
   *   a year range.
   * @param float|null $value
   *   The value, or NULL when unavailable.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup|null $unit
   *   The unit, for example 'euros'.
   * @param string|null $period
   *   The period the value describes, for example '2026Q2'.
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Figure[] $breakdown
   *   Sub-figures, used by the dwellings dataset.
   */
  public function __construct(
    public string $key,
    public string|TranslatableMarkup $label,
    public ?float $value,
    public string|TranslatableMarkup|null $unit = NULL,
    public ?string $period = NULL,
    public array $breakdown = [],
  ) {
  }

  /**
   * Whether the figure has a value.
   *
   * @return bool
   *   TRUE when a value is available.
   */
  public function hasValue() : bool {
    return $this->value !== NULL;
  }

}
