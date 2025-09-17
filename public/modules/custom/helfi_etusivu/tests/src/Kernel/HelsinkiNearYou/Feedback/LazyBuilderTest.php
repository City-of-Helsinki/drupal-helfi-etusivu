<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\Feedback;

use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_api_base\ServiceMap\DTO\StreetName;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\LazyBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use GuzzleHttp\Psr7\Response;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests feedback lazy builder.
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
   * Tests :build.
   */
  public function testBuild() : void {
    $client = $this->createMockHttpClient([
      new Response(200, body: json_encode([
        [
          'description' => '123',
          'lat' => 1,
          'long' => 1,
          'address' => 'Kotikatu 1',
          'service_request_id' => '1',
          'status' => 'invalid',
          'requested_datetime' => 'now',
        ],
      ])),
    ]);
    $this->container->set('http_client', $client);
    $sut = $this->container->get(LazyBuilder::class);

    $address = new Address(
      StreetName::createFromArray(['fi' => 'Insinöörikatu 1']),
      new Location(1, 1, 'Point'),
    );
    $build = $sut->build($address, 'fi', NULL);

    $this->assertEquals(['max-age' => 0], $build['#cache']);
    $this->assertEquals('123', $build['#content'][0]['#title']);
  }

}
