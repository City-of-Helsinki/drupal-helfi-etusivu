<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\HelsinkiNearYou\VehicleRemoval;

use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_api_base\ServiceMap\DTO\StreetName;
use Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval\DTO\Item;
use Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval\VehicleRemovalClient;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\helfi_api_base\Traits\EnvironmentResolverTrait;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the vehicle removal client.
 *
 * @group helfi_etusivu
 */
class VehicleRemovalClientTest extends UnitTestCase {

  use ApiTestTrait;
  use EnvironmentResolverTrait;

  /**
   * Tests a parking zone client.
   */
  public function testParkingZoneResult(): void {
    $resolver = $this->getEnvironmentResolver(Project::ETUSIVU, EnvironmentEnum::Local);
    $address = new Address(
      StreetName::createFromArray(['fi' => 'Mannerheimintie', 'sv' => 'Mannerheimvägen']),
      new Location(60.17, 24.94, 'Point'),
    );

    $httpClient = $this->createMockHttpClient([
      new Response(200, body: (string) json_encode([
        'parkingzone' => 'Kluuvi/Kruununhaka',
        'results' => [[
          '_source' => [
            'address' => ['Mannerheimintie 1'],
            'valid_from' => [1767225600],
            'valid_to' => [1767484800],
            'map_url' => ['https://kartta.hel.fi/?id=1'],
            'time_range' => ['8-18'],
          ],
        ],
        ],
      ])),
    ]);

    $result = (new VehicleRemovalClient($httpClient, $resolver))
      ->getMobileNoteData($address);

    $this->assertEquals('Kluuvi/Kruununhaka', $result->parkingZone);
    $this->assertNull($result->postalCodes);
    $this->assertCount(1, $result->results);

    $item = $result->results[0];
    $this->assertInstanceOf(Item::class, $item);
    $this->assertEquals('Mannerheimintie 1', $item->title);
    $this->assertEquals('Mannerheimintie 1', $item->address);
    $this->assertEquals(date('j.', 1767225600) . '-' . date('d.n.Y', 1767484800), $item->date_range);
    $this->assertEquals('https://kartta.hel.fi/?id=1', $item->map_url);
    $this->assertEquals('8-18', $item->time_range);
  }

}
