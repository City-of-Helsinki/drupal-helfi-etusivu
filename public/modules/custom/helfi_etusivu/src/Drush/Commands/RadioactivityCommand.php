<?php

declare(strict_types=1);

namespace Drupal\helfi_recommendations\Drush\Commands;

use Drupal\radioactivity\Hook\RadioactivityCronHooks;
use Drush\Attributes\Command;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
final class RadioactivityCommand extends DrushCommands {

  use AutowireTrait;

  public function __construct(private RadioactivityCronHooks $cronHooks) {
    parent::__construct();
  }

  /**
   * Drush command to run radioactivity cron functionalities.
   *
   * Ultimate-cron was installed just to schedule the radioactivity.
   * This is a simpler implementation to get rid of ultimate cron -module.
   *
   * @return int
   */
  #[Command(name: 'helfi:radioactivity')]
  public function radioactivity(): int {
    $this->cronHooks->cron();
    return DrushCommands::EXIT_SUCCESS;
  }

}
