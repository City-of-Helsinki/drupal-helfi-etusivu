<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\RoadworkData;

use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_api_base\ServiceMap\DTO\StreetName;
use Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\LazyBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests roadwork lazy builder.
 *
 * @group helfi_etusivu
 */
class LazyBuilderTest extends KernelTestBase {

  use ProphecyTrait;
  use ApiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_etusivu',
    'big_pipe',
    'system',
  ];

  /**
   * Tests build.
   */
  public function testBuild() : void {
    $client = $this->createMockHttpClient([
      new Response(200, body: json_encode([
        'type' => 'FeatureCollection',
        'totalFeatures' => 1,
        'features' => [
          [
            'type' => 'Feature',
            'id' => 'Kaivuilmoitus_alue.820',
            'properties' => [
              'osoite' => '123',
              'tyo_alkaa' => '2025-01-21',
              'tyo_paattyy' => '2025-01-22',
            ],
            'geometry' => [
              'type' => 'MultiPolygon',
              'coordinates' => [
                [
                  [
                    [25505630, 6678516],
                    [25505629, 6678515],
                    [25505629, 6678515],
                  ],
                ],
              ],
            ],
          ],
        ],
      ])),
      new ConnectException('Connection timed out', new Request('GET', 'http://example.com/')),
    ]);
    $this->container->set('http_client', $client);

    /** @var \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\LazyBuilder $sut */
    $sut = $this->container->get(LazyBuilder::class);

    $address = new Address(
      StreetName::createFromArray(['fi' => 'Insinöörikatu 1']),
      new Location(1, 1, 'Point'),
    );
    $build = $sut->build($address, 'fi');

    $this->assertEquals(['max-age' => 0], $build['#cache']);
    $this->assertEquals('123', $build['#content'][0]['#title']);

    // Error variable is set if API request fails.
    $build = $sut->build($address, 'fi');
    $this->assertTrue($build['#error']);
  }

}
