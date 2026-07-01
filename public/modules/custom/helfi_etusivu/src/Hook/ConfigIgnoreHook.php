<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hooks for config_ignore module.
 */
class ConfigIgnoreHook {

  /**
   * Implements hook_config_ignore_settings_alter().
   *
   * @phpstan-param array<string> $settings
   */
  #[Hook('config_ignore_settings_alter')]
  public static function alter(array &$settings): void {
    $settings[] = 'helfi_etusivu.llms_txt:content';
  }

}
