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
    $service = \Drupal::service(RadioactivityCommand::class);
    $previousRun = \Drupal::state()->get(RadioactivityProcessorInterface::LAST_PROCESSED_STATE_KEY);

    /** @var \Drupal\Core\Cron $cron */
    $cron = \Drupal::service('cron');
    $cron->run();

    $this->assertEquals(
      $previousRun,
      \Drupal::state()->get(RadioactivityProcessorInterface::LAST_PROCESSED_STATE_KEY),
      'Assert that cron did not execute radioactivity'
    );

    $service->radioactivity();

    $this->assertNotEquals(
      $previousRun,
      \Drupal::state()->get(RadioactivityProcessorInterface::LAST_PROCESSED_STATE_KEY),
      'Assert that custom command executed radioactivity'
    );
  }

}
