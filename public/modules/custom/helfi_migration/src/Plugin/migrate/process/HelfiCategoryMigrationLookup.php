<?php

namespace Drupal\helfi_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Row;

/**
 * Looks up the value of a property based on a previous migration.
 * Handles Helfi migration specifics.
 *
 * @MigrateProcessPlugin(
 *   id = "helfi_category_migration_lookup"
 * )
 */
class HelfiCategoryMigrationLookup extends MigrationLookup {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   * @throws \Drupal\migrate\MigrateException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $categorySegments = explode('/', $value);
    $value = 'wcmrest:' . end($categorySegments);
    parent::transform($value, $migrate_executable, $row, $destination_property);
  }

  /**
   * Skips the migration process entirely if the value is invalid.
   *
   * @param array $value
   *   The incoming value to check.
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   */
  protected function skipInvalid(array $value) {
    if (!array_filter($value, [$this, 'isValid'])) {
      throw new MigrateSkipProcessException();
    }
  }

  /**
   * Determines if the value is valid for lookup.
   *
   * The only values considered invalid are: NULL, FALSE, [] and "".
   *
   * @param string $value
   *   The value to test.
   *
   * @return bool
   *   Return true if the value is valid.
   */
  protected function isValid($value) {
    return !in_array($value, [NULL, FALSE, [], ""], TRUE);
  }

}
