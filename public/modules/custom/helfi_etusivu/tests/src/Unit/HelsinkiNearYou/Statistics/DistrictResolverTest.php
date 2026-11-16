<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\HelsinkiNearYou\Statistics;

use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DistrictResolver;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException;
use GuzzleHttp\Psr7\Response;

/**
 * @coversDefaultClass \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DistrictResolver
 * @group helfi_etusivu
 */
class DistrictResolverTest extends StatisticsTestBase {

  /**
   * Tests resolving coordinates into a district.
   *
   * The code has to come from the statistical district, whose origin_id is
   * the statistics API's area key, while the names come from the sub district,
   * which is the only one of the two that carries Swedish.
   *
   * Only one response is queued, so the second, nearby lookup has to be served
   * from cache or the mock handler fails.
   *
   * @covers ::resolve
   */
  public function testResolve() : void {
    $http = $this->createMockHttpClient([
      new Response(200, body: json_encode([
        'count' => 2,
        'results' => [
          [
            'origin_id' => '010',
            'type' => 'sub_district',
            'name' => ['fi' => 'Kruununhaka', 'sv' => 'Kronohagen'],
          ],
          [
            'origin_id' => '0911101010',
            'ocd_id' => 'ocd-division/country:fi/tilastoalue:0911101010',
            'type' => 'statistical_district',
            'name' => ['fi' => 'Kruununhaka'],
          ],
        ],
      ], JSON_THROW_ON_ERROR)),
    ]);
    $sut = new DistrictResolver($this->getApiClient($http));

    $district = $sut->resolve(new Location(60.16848194, 24.95161762, 'Point'));

    $this->assertSame('0911101010', $district->code);
    $this->assertSame('Kruununhaka', $district->getName('fi'));
    $this->assertSame('Kronohagen', $district->getName('sv'));
    // No source has English district names.
    $this->assertSame('Kruununhaka', $district->getName('en'));

    $nearby = $sut->resolve(new Location(60.16848191, 24.95161764, 'Point'));
    $this->assertSame($district->code, $nearby->code, 'nearby coordinates share a cache entry');
  }

  /**
   * Tests the cases that yield no district.
   *
   * A sub district on its own is not enough: its origin_id is a three digit
   * local code, not the statistics API's area key, so accepting it would send
   * a meaningless code to PxWeb.
   *
   * @covers ::resolve
   */
  public function testNoDistrict() : void {
    $cases = [
      'no results' => ['count' => 0, 'results' => []],
      'sub district only' => [
        'count' => 1,
        'results' => [
          [
            'origin_id' => '010',
            'type' => 'sub_district',
            'name' => ['fi' => 'Kruununhaka', 'sv' => 'Kronohagen'],
          ],
        ],
      ],
    ];

    foreach ($cases as $name => $body) {
      $http = $this->createMockHttpClient([new Response(200, body: json_encode($body, JSON_THROW_ON_ERROR))]);
      $sut = new DistrictResolver($this->getApiClient($http));

      $this->assertNull($sut->resolve(new Location(60.0, 24.0, 'Point')), "case: $name");
    }
  }

  /**
   * Tests that a failing request is wrapped in a StatisticsException.
   *
   * @covers ::resolve
   */
  public function testRequestFailure() : void {
    $http = $this->createMockHttpClient([new Response(500)]);
    $sut = new DistrictResolver($this->getApiClient($http));

    $this->expectException(StatisticsException::class);
    $sut->resolve(new Location(60.1684819, 24.9516176, 'Point'));
  }

}
