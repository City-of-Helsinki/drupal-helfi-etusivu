<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\HelsinkiNearYou\Statistics;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\JsonClient;
use GuzzleHttp\ClientInterface;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Base class for statistics unit tests.
 */
abstract class StatisticsTestBase extends UnitTestCase {

  use ApiTestTrait;
  use ProphecyTrait;

  /**
   * Constructs a JSON client backed by the given HTTP client.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Cache\MemoryBackend|null $cache
   *   The cache backend, or NULL for an empty one.
   * @param int $requestTime
   *   The request time.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\JsonClient
   *   The JSON client.
   */
  protected function getJsonClient(ClientInterface $httpClient, ?MemoryBackend $cache = NULL, int $requestTime = 1757980800) : JsonClient {
    $time = $this->prophesize(TimeInterface::class);
    $time->getRequestTime()->willReturn($requestTime);

    return new JsonClient(
      $httpClient,
      $cache ?? new MemoryBackend($time->reveal()),
      $time->reveal(),
      15,
    );
  }

  /**
   * Builds a single cell json-stat2 dataset.
   *
   * @param mixed $value
   *   The cell value.
   *
   * @return array<mixed>
   *   The dataset.
   */
  protected function singleValueDataset(mixed $value) : array {
    return [
      'id' => ['Alue', 'Vuosi', 'Tiedot'],
      'size' => [1, 1, 1],
      'value' => [$value],
    ];
  }

  /**
   * Builds table metadata with the given period values.
   *
   * @param string $variable
   *   The period variable code.
   * @param array<string> $values
   *   The period values, oldest first.
   *
   * @return array<mixed>
   *   The metadata.
   */
  protected function metadata(string $variable, array $values) : array {
    return [
      'title' => 'Test table',
      'variables' => [
        ['code' => 'Alue', 'text' => 'Alue', 'values' => ['0911101010']],
        ['code' => $variable, 'text' => $variable, 'values' => $values],
      ],
    ];
  }

  /**
   * Asserts a label is translatable and returns its source string.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   The label or unit.
   *
   * @return string
   *   The untranslated source string.
   */
  protected function untranslated(string|TranslatableMarkup|null $label) : string {
    $this->assertInstanceOf(TranslatableMarkup::class, $label);

    return $label->getUntranslatedString();
  }

}
