<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_etusivu\Enum\ServiceMapLink;
use Drupal\helfi_etusivu\HelsinkiNearYou\ServiceMap;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests linked events helper service.
 */
class ServiceMapTest extends KernelTestBase {

  use ProphecyTrait;
  use ApiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'helfi_etusivu',
  ];

  /**
   * Gets the SUT.
   *
   * @param \Prophecy\Prophecy\ObjectProphecy $client
   *   The client mock.
   * @param \Prophecy\Prophecy\ObjectProphecy $logger
   *   The logger mock.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\ServiceMap
   *   The SUT.
   */
  private function getSut(ObjectProphecy $client, ObjectProphecy $logger) : ServiceMap {
    $sut = new ServiceMap(
      $client->reveal(),
      $this->container->get(LanguageManagerInterface::class),
      $logger->reveal(),
    );
    return $sut;
  }

  /**
   * Tests failing request.
   */
  public function testQueryGuzzleException() : void {
    $client = $this->prophesize(ClientInterface::class);
    $client->request('GET', Argument::any(), Argument::any())
      ->shouldBeCalled()
      ->willThrow(
        new ClientException(
          'Fail.',
          $this->prophesize(RequestInterface::class)->reveal(),
          new Response(400)
        )
      );
    $logger = $this->prophesize(LoggerInterface::class);
    $logger->error('Servicemap query failed: Fail.')->shouldBeCalled();

    $sut = $this->getSut($client, $logger);
    $this->assertEmpty($sut->query('123'));
  }

  /**
   * Tests query().
   */
  public function testQuery() : void {
    $client = $this->prophesize(ClientInterface::class);
    $client->request('GET', Argument::any(), Argument::any())
      ->shouldBeCalled()
      ->willReturn(
        new Response(body: ''),
        new Response(body: json_encode(['results' => ['123']])),
      );
    $logger = $this->prophesize(LoggerInterface::class);
    $logger->error('Servicemap query failed: Unexpected response. Results not present.')->shouldBeCalled();

    $sut = $this->getSut($client, $logger);
    // Make sure the first request fails to empty results.
    $this->assertEmpty($sut->query('123'));
    $this->assertEquals(['123'], $sut->query('123'));
  }

  /**
   * Tests getAddressData().
   */
  public function testGetAddressData() : void {
    $client = $this->prophesize(ClientInterface::class);
    $client->request('GET', Argument::any(), Argument::any())
      ->shouldBeCalled()
      ->willReturn(
        new Response(body: ''),
        new Response(body: json_encode([
          'results' => [
            [
              'name' => '123',
              'location' => ['coordinates' => [123, 321]],
            ],
          ],
        ])),
      );
    $logger = $this->prophesize(LoggerInterface::class);
    $logger->error('Servicemap query failed: Unexpected response. Results not present.')->shouldBeCalled();

    $sut = $this->getSut($client, $logger);
    $this->assertNull($sut->getAddressData('123'));
    $this->assertEquals([
      'address_translations' => '123',
      'coordinates' => [123, 321],
    ], $sut->getAddressData('123'));
  }

  /**
   * Tests ::getLink().
   */
  public function testGetLink() : void {
    $sut = $this->getSut($this->prophesize(ClientInterface::class), $this->prophesize(LoggerInterface::class));
    $response = $sut->getLink(ServiceMapLink::PLANS_IN_PROCESS, '123');
    $this->assertEquals('https://kartta.hel.fi/?addresslabel=Plans%20under%20preparation%20near%20the%20address%20123&addresslocation=123&link=eDB7Rk&setlanguage=en', $response);
  }

}
