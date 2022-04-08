<?php

declare(strict_types = 1);

namespace Drupal\Tests\dtt\ExistingSiteJavascript;

use Drupal\Tests\dtt\Traits\DefaultConfigurationTrait;
use weitzman\DrupalTestTraits\ExistingSiteWebDriverTestBase;

/**
 * Existing site test base.
 */
abstract class ExistingSiteJavascriptTestBase extends ExistingSiteWebDriverTestBase {

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
