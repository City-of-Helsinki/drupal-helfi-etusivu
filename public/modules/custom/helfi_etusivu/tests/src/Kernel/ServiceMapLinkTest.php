<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\helfi_etusivu\Enum\ServiceMapLink;

/**
 * Tests the ServiceMapLink enum.
 *
 * @group helfi_etusivu
 */
class ServiceMapLinkTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [];

  /**
   * Tests the link() method of ServiceMapLink enum.
   */
  public function testLinkMethod(): void {
    $this->assertSame('eDAB7W', ServiceMapLink::ROADWORK_EVENTS->link());
    $this->assertSame('eRqwiU', ServiceMapLink::CITYBIKE_STATIONS_STANDS->link());
    $this->assertSame('eDBTcc', ServiceMapLink::STREET_PARK_PROJECTS->link());
    $this->assertSame('eDB7Rk', ServiceMapLink::PLANS_IN_PROCESS->link());
  }

}
