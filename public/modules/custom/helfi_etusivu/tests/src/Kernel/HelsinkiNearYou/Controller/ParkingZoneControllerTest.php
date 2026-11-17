<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\Controller;

use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_api_base\ServiceMap\DTO\ParkingZone;
use Drupal\helfi_api_base\ServiceMap\DTO\StreetName;
use Drupal\helfi_api_base\ServiceMap\ResidentParkingZoneService;
use Drupal\helfi_api_base\ServiceMap\ResidentParkingZoneServiceInterface;
use Drupal\helfi_api_base\ServiceMap\ServiceMap;
use Drupal\helfi_api_base\ServiceMap\ServiceMapInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\ParkingZoneController;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the resident parking zone HTMX controller.
 */
#[CoversClass(ParkingZoneController::class)]
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
class ParkingZoneControllerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_api_base',
    'diff',
    'helfi_etusivu',
    'system',
  ];

  /**
   * Tests the htmx() callback.
   */
  public function testHtmx(): void {
    $location = new Location(60.1849, 24.9496, 'Point');
    $serviceMap = $this->createMock(ServiceMapInterface::class);
    $serviceMap->method('getAddressData')
      ->with('Kolmas linja 10')
      ->willReturn(new Address(StreetName::createFromArray(['fi' => 'Kolmas linja 10']), $location));

    $parkingZoneService = $this->createMock(ResidentParkingZoneServiceInterface::class);
    $parkingZoneService->expects($this->once())
      ->method('getParkingZone')
      ->with($location)
      ->willReturn(new ParkingZone('Alppiharju'));

    $this->container->set(ServiceMap::class, $serviceMap);
    $this->container->set(ResidentParkingZoneService::class, $parkingZoneService);
    $controller = ParkingZoneController::create($this->container);

    // Nothing is shown without an address, and the empty response varies by
    // query so it is not cached for every address.
    $this->assertEquals(['#cache' => ['contexts' => ['url.query_args']]], $controller->htmx(new Request()));

    $build = $controller->htmx(new Request(['home_address' => 'Kolmas linja 10']));
    $this->assertEquals('helsinki_near_you_parking_zone', $build['#content']['#theme']);
    $this->assertEquals('Alppiharju', $build['#content']['#name']);
  }

}
