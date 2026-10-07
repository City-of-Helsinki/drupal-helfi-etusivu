<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Search\Numerot;

use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\migrate\Kernel\MigrateSourceTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;

/**
 * Base class for testing numerot.hel.fi source plugins.
 */
abstract class NumerotSourceTestBase extends MigrateSourceTestBase {

  use ApiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'diff',
    'helfi_api_base',
    'helfi_etusivu',
    'migrate',
  ];

  /**
   * The requested URLs.
   *
   * @var string[]
   */
  protected array $requests = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('helfi_etusivu.numerot')
      ->set('api_key', 'test-api-key')
      ->set('app_id', 'test-app-id')
      ->save();
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, string> $source_data
   *   The fixture names keyed by URL.
   * @param array<array<string, mixed>> $expected_data
   *   The expected rows.
   * @param mixed $expected_count
   *   The expected count.
   * @param array<string, mixed> $configuration
   *   The source plugin configuration.
   * @param mixed $high_water
   *   The high water value.
   */
  #[DataProvider('providerSource')]
  public function testSource(array $source_data, array $expected_data, $expected_count = NULL, array $configuration = [], $high_water = NULL): void {
    $this->setSourceData($source_data);
    parent::testSource($source_data, $expected_data, $expected_count, $configuration, $high_water);
  }

  /**
   * Serves the given fixtures to the source plugin.
   *
   * @param array<string, string> $fixtures
   *   The fixture names keyed by URL.
   */
  protected function setSourceData(array $fixtures): void {
    $fixtures = array_combine(array_map(urldecode(...), array_keys($fixtures)), $fixtures);
    $this->requests = [];

    $handler = function (RequestInterface $request) use ($fixtures) {
      $this->assertEquals('test-api-key', $request->getHeaderLine('api-key'));
      $this->assertEquals('test-app-id', $request->getHeaderLine('auth-method'));

      $url = urldecode((string) $request->getUri());
      $this->requests[] = $url;

      return Create::promiseFor(isset($fixtures[$url])
        ? new Response(200, [], $this->getFixture('helfi_etusivu', "numerot/$fixtures[$url]"))
        : new Response(404));
    };

    $this->container->set('http_client', new Client(['handler' => HandlerStack::create($handler)]));
  }

  /**
   * Iterates the source plugin and returns the source rows.
   *
   * @param array<string, mixed> $configuration
   *   The source plugin configuration.
   *
   * @return array<array<string, mixed>>
   *   The rows.
   */
  protected function getRows(array $configuration = []): array {
    $plugin = $this->getPlugin($configuration);
    $this->assertInstanceOf(MigrateSourceInterface::class, $plugin);

    $rows = [];
    foreach ($plugin as $row) {
      $rows[] = $row->getSource();
    }
    return $rows;
  }

}
