<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\Feedbacks;

use Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\LazyBuilder;
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
    $build = $sut->build(1, 1, NULL, NULL);

    $this->assertEquals(['max-age' => 0], $build['#cache']);
    $this->assertEquals('123', $build['items'][0]['#title']);
  }

}
