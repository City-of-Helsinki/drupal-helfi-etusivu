<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\HelsinkiNearYou\Feedback;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\Client;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Feedback;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Request;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

/**
 * Tests the Feedback client.
 *
 * @group helfi_etusivu
 */
class ClientTest extends UnitTestCase {

  use ApiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $container = new ContainerBuilder();
    $languageManager = $this->createMock(LanguageManagerInterface::class);
    $languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => $this->randomMachineName(2)]));
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('language_manager', $languageManager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests API failures.
   */
  public function testFailedResponse() : void {
    $httpClient = $this->createMockHttpClient([
      new Response(200, body: ''),
      new Response(400, body: ''),
    ]);
    $sut = new Client($httpClient);
    $request = new Request(
      lat: 1,
      lon: 1,
      radius: 0.5,
    );
    // Test empty JSON response.
    $response = $sut->get($request);
    $this->assertEmpty($response->items);
    // Test 400 error.
    $response = $sut->get($request);
    $this->assertEmpty($response->items);
  }

  /**
   * Tests uri construction.
   *
   * @dataProvider uriData
   */
  public function testUri(Request $request, string $expectedUri) : void {
    $client = $this->prophesize(ClientInterface::class);
    $client->request('GET', $expectedUri, Argument::any())
      ->shouldBeCalled()
      ->willReturn(new Response(200, body: ''));

    $sut = new Client($client->reveal());
    $sut->get($request);
  }

  /**
   * Data provider for testUri().
   *
   * @return array[]
   *   The data.
   */
  public function uriData(): array {
    return [
      [
        new Request(lat: 1, lon: 1, radius: 0.5),
        'https://palautteet.hel.fi/public-api/open311-public-service/v1/requests.json?extensions=1&lat=1&long=1&radius=0.5',
      ],
      [
        new Request(lat: 1, lon: 1, radius: 0.5, limit: 10, start_date: new DrupalDateTime('2025-01-31 23:59:59', settings: ['langcode' => 'en'])),
        'https://palautteet.hel.fi/public-api/open311-public-service/v1/requests.json?extensions=1&lat=1&long=1&radius=0.5&start_date=2025-01-31T23%3A59%3A59Z',
      ],
    ];
  }

  /**
   * Make sure invalid API response items are skipped.
   */
  public function testWithInvalidItem() : void {
    $httpClient = $this->createMockHttpClient([
      new Response(200, body: json_encode([
        [
          'description' => '123',
          'lat' => 1,
          'long' => 1,
          'address' => 'Kotikatu 1',
          'service_request_id' => '1',
          'title' => 'Title',
          'status' => 'invalid',
          'requested_datetime' => 'now',
        ],
        [
          'description' => 2,
        ],
      ])),
    ]);
    $sut = new Client($httpClient);
    $request = new Request(
      lat: 1,
      lon: 1,
      radius: 0.5,
    );
    $response = $sut->get($request);
    $this->assertCount(1, $response->items);
    $this->assertInstanceOf(Feedback::class, $response->items[0]);
  }

}
