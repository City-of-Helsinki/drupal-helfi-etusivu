<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit;

use Drupal\helfi_etusivu\HelsinkiNearYou\Enum\ServiceMapLink;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\helfi_etusivu\HelsinkiNearYou\Enum\ServiceMapLink
 *
 * @group helfi_etusivu
 */
class ServiceMapLinkTest extends TestCase {

  /**
   * @covers ::link
   */
  public function testLinkMethod(): void {
    $this->assertSame('eDAB7W', ServiceMapLink::ROADWORK_EVENTS->link());
    $this->assertSame('eRqwiU', ServiceMapLink::CITYBIKE_STATIONS_STANDS->link());
    $this->assertSame('eDBTcc', ServiceMapLink::STREET_PARK_PROJECTS->link());
    $this->assertSame('eDB7Rk', ServiceMapLink::PLANS_IN_PROCESS->link());
  }

}
