<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\HelsinkiNearYou\Statistics;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\JsonClient;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException;
use GuzzleHttp\Psr7\Response;

/**
 * @coversDefaultClass \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\JsonClient
 * @group helfi_etusivu
 */
class JsonClientTest extends StatisticsTestBase {

  /**
   * Tests caching, expiry and the stale fallback.
   *
   * The mock handler fails on an unexpected request, so every lookup that is
   * meant to be served from cache is checked by not queueing a response.
   *
   * @covers ::request
   */
  public function testRequest() : void {
    $time = $this->prophesize(TimeInterface::class);
    $time->getRequestTime()->willReturn(1000);
    $cache = new MemoryBackend($time->reveal());

    $http = $this->createMockHttpClient([
      new Response(200, body: '{"value": 1}'),
      new Response(200, body: '{"value": 2}'),
      new Response(500),
    ]);
    $sut = new JsonClient($http, $cache, $time->reveal(), 15);

    $this->assertSame(['value' => 1], $sut->request('key', 10, 'GET', 'https://example.com'));
    $this->assertSame(['value' => 1], $sut->request('key', 10, 'GET', 'https://example.com'), 'served from cache');

    $time->getRequestTime()->willReturn(1011);
    $this->assertSame(['value' => 2], $sut->request('key', 10, 'GET', 'https://example.com'), 'refreshed when expired');

    $time->getRequestTime()->willReturn(1022);
    $this->assertSame(['value' => 2], $sut->request('key', 10, 'GET', 'https://example.com'), 'stale entry served on failure');
  }

  /**
   * Tests that failures without a cached entry are wrapped.
   *
   * @covers ::request
   */
  public function testRequestFailure() : void {
    foreach ([new Response(500), new Response(200, body: 'not json')] as $response) {
      $sut = $this->getJsonClient($this->createMockHttpClient([$response]));

      try {
        $sut->request('key', 10, 'GET', 'https://example.com');
        $this->fail('Expected a StatisticsException.');
      }
      catch (StatisticsException $e) {
        $this->assertNotNull($e->getPrevious());
      }
    }
  }

}
