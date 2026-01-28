<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\LinkedEvents;

use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\Client;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the LinkedEvents client.
 *
 * @group helfi_etusivu
 */
class ClientTest extends KernelTestBase {

  use ApiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_api_base',
    'helfi_etusivu',
    'system',
  ];

  /**
   * Tests API failures.
   */
  public function testFailedResponse() : void {
    $httpClient = $this->createMockHttpClient([
      new Response(200, body: ''),
      new Response(400, body: ''),
    ]);
    $sut = new Client($httpClient);
    // Test empty JSON response.
    $response = $sut->get([], 'fi', 3);
    $this->assertEquals(0, $response->numItems);
    $this->assertEmpty($response->items);
    // Test 400 error.
    $response = $sut->get([], 'fi', 3);
    $this->assertEquals(0, $response->numItems);
    $this->assertEmpty($response->items);
  }

  /**
   * Tests getUri().
   */
  public function testGetUri() : void {
    $sut = $this->container->get(Client::class);
    $this->assertEquals('https://api.hel.fi/linkedevents/v1/event/?event_type=General&format=json&include=keywords%2Clocation&page=1&page_size=5&sort=end_time&start=now&super_event_type=umbrella%2Cnone&language=en&all_ongoing=true', $sut->getUri('en', [], 5));
  }

}
