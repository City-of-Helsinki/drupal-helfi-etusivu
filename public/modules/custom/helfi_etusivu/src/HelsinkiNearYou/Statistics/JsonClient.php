<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Statistics;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Makes cached JSON requests for the statistics feature.
 *
 * An expired entry is kept and served when refreshing it fails, so an outage
 * of an upstream API does not empty the page until the outage is over.
 */
final readonly class JsonClient {

  public const CACHE_TAG = 'helfi_etusivu_statistics';

  public function __construct(
    private ClientInterface $httpClient,
    #[Autowire(service: 'cache.default')]
    private CacheBackendInterface $cache,
    private TimeInterface $time,
    #[Autowire(param: 'helfi_etusivu.statistics_request_timeout')]
    private int $timeout,
  ) {
  }

  /**
   * Performs a cached request and decodes the JSON response.
   *
   * @param string $cacheKey
   *   The cache key.
   * @param int $ttl
   *   The cache lifetime in seconds.
   * @param string $method
   *   The HTTP method.
   * @param string $uri
   *   The URI.
   * @param array<string, mixed> $options
   *   The Guzzle request options.
   *
   * @return array<mixed>
   *   The decoded response.
   *
   * @throws \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException
   *   Thrown when the request fails and there is no cached response.
   */
  public function request(string $cacheKey, int $ttl, string $method, string $uri, array $options = []) : array {
    $cached = $this->cache->get($cacheKey, allow_invalid: TRUE);

    if ($cached && $cached->valid) {
      return $cached->data;
    }

    try {
      $response = $this->httpClient->request($method, $uri, $options + [
        RequestOptions::TIMEOUT => $this->timeout,
      ]);
      $data = json_decode($response->getBody()->getContents(), TRUE, flags: JSON_THROW_ON_ERROR);
    }
    catch (GuzzleException | \JsonException $e) {
      if ($cached) {
        return $cached->data;
      }
      throw new StatisticsException($e->getMessage(), previous: $e);
    }

    $data = is_array($data) ? $data : [];
    $this->cache->set($cacheKey, $data, $this->time->getRequestTime() + $ttl, [self::CACHE_TAG]);

    return $data;
  }

}
