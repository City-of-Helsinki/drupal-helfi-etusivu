<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Statistics;

/**
 * Reads values and categories out of a json-stat2 dataset.
 *
 * Values arrive as a single flat array, in row-major order of the dimensions
 * listed in 'id' with their lengths in 'size'.
 */
final readonly class JsonStat2 {

  public function __construct(private array $data) {
  }

  /**
   * Returns the value at the given cube coordinates.
   *
   * @param array $coordinates
   *   Category indexes keyed by dimension name. Omitted dimensions default to
   *   index 0, which is what a dimension pinned to one value needs.
   *
   * @return float|null
   *   The value, or NULL when the cell is suppressed, absent or not numeric.
   */
  public function value(array $coordinates = []) : ?float {
    $value = $this->data['value'][$this->offset($coordinates)] ?? NULL;

    return is_numeric($value) ? (float) $value : NULL;
  }

  /**
   * Returns a dimension's category keys in cube order.
   *
   * @param string $dimension
   *   The dimension name.
   *
   * @return string[]
   *   The category keys, ordered by their index.
   */
  public function categories(string $dimension) : array {
    $index = $this->data['dimension'][$dimension]['category']['index'] ?? [];
    asort($index);

    // Numeric keys such as the building type '1' become integers once used as
    // array keys.
    return array_map(strval(...), array_keys($index));
  }

  /**
   * Returns the label of one category.
   *
   * @param string $dimension
   *   The dimension name.
   * @param string $key
   *   The category key.
   *
   * @return string|null
   *   The label, or NULL when the dataset does not name the category.
   */
  public function label(string $dimension, string $key) : ?string {
    $labels = $this->data['dimension'][$dimension]['category']['label'] ?? [];

    return isset($labels[$key]) ? (string) $labels[$key] : NULL;
  }

  /**
   * Resolves the flat offset of a cell.
   *
   * The strides are derived from 'id' and 'size' so that this stays correct
   * if the API reorders dimensions.
   *
   * @param array $coordinates
   *   Category indexes keyed by dimension name.
   *
   * @return int
   *   The offset into 'value'.
   */
  private function offset(array $coordinates) : int {
    $offset = 0;

    foreach ($this->data['id'] ?? [] as $position => $dimension) {
      $size = (int) ($this->data['size'][$position] ?? 1);
      $offset = $offset * $size + ($coordinates[$dimension] ?? 0);
    }

    return $offset;
  }

}
