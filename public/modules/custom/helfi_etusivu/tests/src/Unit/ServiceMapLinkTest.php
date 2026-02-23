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
    $this->assertSame('eDAB7W', ServiceMapLink::RoadworkEvents->link());
    $this->assertSame('eRqwiU', ServiceMapLink::CityBikeStationsStands->link());
    $this->assertSame('eDBTcc', ServiceMapLink::StreetParkProjects->link());
    $this->assertSame('eDB7Rk', ServiceMapLink::PlansInProcess->link());
  }

}
