<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\ExistingSite;

use Drupal\helfi_etusivu\Drush\Commands\RadioactivityCommand;
use Drupal\radioactivity\RadioactivityProcessorInterface;
use Drupal\Tests\helfi_api_base\Functional\ExistingSiteTestBase;

/**
 * Tests radioactivity customization.
 *
 * @group helfi_etusivu
 */
class RadioactivityTest extends ExistingSiteTestBase {

  /**
   * Tests custom radioactivity cron implementation.
   *
   * Radioactivity module's cron hook should be disabled and
   * custom command should execute the original functionality.
   */
  public function testCustomRadioactivityCron(): void {
    $command = RadioactivityCommand::create($this->container);
    $previousRun = \Drupal::state()->get(RadioactivityProcessorInterface::LAST_PROCESSED_STATE_KEY);

    $cron = \Drupal::service('cron');
    $cron->run();

    $this->assertEquals(
      $previousRun,
      \Drupal::state()->get(RadioactivityProcessorInterface::LAST_PROCESSED_STATE_KEY),
      'Assert that cron did not execute radioactivity'
    );

    $command->radioactivity();

    $this->assertNotEquals(
      $previousRun,
      \Drupal::state()->get(RadioactivityProcessorInterface::LAST_PROCESSED_STATE_KEY),
      'Assert that custom command executed radioactivity'
    );
  }

}
