<?php

namespace Drupal\helfi_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\FormatDate;
use Drupal\migrate\Row;

/**
 * Process plugin for newsitem content.
 *
 * @MigrateProcessPlugin(
 *   id = "helfi_format_date"
 * )
 */
class HelfiFormatDate extends FormatDate {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = substr($value, 0, 19);
    parent::transform($value, $migrate_executable, $row, $destination_property);
  }

}
