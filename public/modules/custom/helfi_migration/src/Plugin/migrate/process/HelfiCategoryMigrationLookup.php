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

}
