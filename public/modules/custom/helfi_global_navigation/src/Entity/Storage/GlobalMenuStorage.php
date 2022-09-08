<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Entity\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Provides a storage class for global navigation entities.
 */
final class GlobalMenuStorage extends SqlContentEntityStorage {

  /**
   * Sort and load all global menu entities..
   *
   * @return \Drupal\helfi_global_navigation\Entity\GlobalMenu[]
   *   An array of global menu entities.
   */
  public function loadMultipleSorted(array $conditions = [], string $field = 'weight', string $direction = 'ASC') : array {
    $query = $this->getQuery()
      ->sort($field, $direction);

    foreach ($conditions as $field => $condition) {
      if (!isset($condition['operator'])) {
        $condition['operator'] = '=';
      }
      ['value' => $value, 'operator' => $operator] = $condition;

      $query->condition($field, $value, $operator);
    }

    $ids = $query->execute();
    return $ids ? $this->loadMultiple($ids) : [];
  }

}
