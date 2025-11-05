<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\LinkedEvents;

use Drupal\Core\Url;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_api_base\ServiceMap\DTO\StreetName;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\LazyBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use GuzzleHttp\Psr7\Response;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests LinkedEvents lazy builder.
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
        'data' => [
          [
            'id' => '123',
            'name' => [
              'fi' => 'title fi',
            ],
            'type_id' => 'Volunteering',
            'location' => ['id' => 'helsinki:internet'],
            'offers' => [
              ['is_free' => TRUE],
              ['info_url' => ['sv' => 'https://localhost']],
            ],
            'images' => [
              ['url' => 'https://localhost/kuva.jpg', 'alt_text' => '123', 'photographer_name' => 'name'],
            ],
            'enrolment_start_time' => '2004-02-12T15:19:21+00:00',
            'enrolment_end_time' => '2004-02-12T15:19:21+00:00',
            'start_time' => '2004-02-12T15:19:21+00:00',
            'end_time' => '2004-02-15T15:19:21+00:00',
          ],
        ],
      ])),
    ]);
    $this->container->set('http_client', $client);
    $sut = $this->container->get(LazyBuilder::class);

    $address = new Address(
      StreetName::createFromArray(['fi' => 'Insinöörikatu 1']),
      new Location(1, 1, 'Point'),
    );
    $build = $sut->build($address, 'fi', 3);

    $item = $build['#content'][0];

    $this->assertEquals(['max-age' => 0], $build['#cache']);
    $this->assertEquals('title fi', $item['#title']['#title']);
    $this->assertInstanceOf(Url::class, $item['#title']['#url']);
    $this->assertEquals('https://tapahtumat.hel.fi/fi/tapahtumat/123', $item['#title']['#url']->toString());

    $this->assertEquals([
      '#theme' => 'imagecache_external_responsive',
      '#uri' => 'https://localhost/kuva.jpg',
      '#responsive_image_style_id' => 'card',
      '#alt' => '123',
    ], $build['#content'][0]['#external_image']);
  }

}
