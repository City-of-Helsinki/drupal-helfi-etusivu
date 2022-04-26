<?php

declare(strict_types = 1);

namespace Drupal\Tests\dtt\ExistingSite;

use Drupal\Tests\dtt\Traits\DefaultConfigurationTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Existing site test base.
 */
abstract class ExistingSiteTestBase extends ExistingSiteBase {

  use DefaultConfigurationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setupDefaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();
    $this->tearDownDefaultConfiguration();
  }

}
