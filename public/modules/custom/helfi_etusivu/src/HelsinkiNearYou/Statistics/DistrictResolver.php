<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Statistics;

use Drupal\helfi_api_base\ApiClient\ApiClient;
use Drupal\helfi_api_base\ApiClient\CacheValue;
use Drupal\helfi_api_base\Cache\CacheKeyTrait;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\District;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Resolves coordinates into a statistical district via the Servicemap API.
 *
 * The 'statistical_district' division's origin_id is the same area code that
 * the Aluesarjat PxWeb API uses, which is what allows statistics to be looked
 * up without a mapping table.
 */
final readonly class DistrictResolver implements DistrictResolverInterface {

  use CacheKeyTrait;
  use DecodedResponseTrait;

  private const API_URL = 'https://api.hel.fi/servicemap/v2/administrative_division/';

  /**
   * The division types to request.
   *
   * 'statistical_district' carries the area code but is named in Finnish only.
   * 'sub_district' covers the same area and carries the Swedish name.
   */
  private const TYPES = 'statistical_district,sub_district';

  private const TTL = 604800;

  public function __construct(
    #[Autowire(service: 'helfi_etusivu.statistics_api_client')]
    private ApiClient $client,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(Location $location) : ?District {
    // Five decimals is about a metre, so neighbouring addresses share a cache
    // entry without crossing a district boundary.
    $query = [
      'lat' => round($location->lat, 5),
      'lon' => round($location->lon, 5),
      'type' => self::TYPES,
    ];
    $key = $this->getCacheKey('helfi_etusivu:statistics:district', $query);

    try {
      $value = $this->client->cache($key, fn () => new CacheValue(
        $this->client->makeRequest('GET', self::API_URL, ['query' => $query]),
        $this->client->cacheMaxAge(self::TTL),
        ['helfi_etusivu_statistics'],
      ));
    }
    catch (GuzzleException | \JsonException $e) {
      throw new StatisticsException($e->getMessage(), previous: $e);
    }

    $data = $value ? $this->toArray($value->response) : [];

    return empty($data['results']) ? NULL : $this->toDistrict($data['results']);
  }

  /**
   * Builds a district from administrative division results.
   *
   * @param array<int, array<string, mixed>> $results
   *   The division results.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\District|null
   *   The district, or NULL when the coordinates are outside every statistical
   *   district.
   */
  private function toDistrict(array $results) : ?District {
    $byType = array_column($results, NULL, 'type');

    // Only the statistical district carries the area code.
    if (empty($byType['statistical_district']['origin_id'])) {
      return NULL;
    }

    // Array union keeps the left operand's keys, so the Finnish-only name of
    // the statistical district fills in only what the sub district lacks.
    $names = ($byType['sub_district']['name'] ?? [])
      + ($byType['statistical_district']['name'] ?? []);

    return District::create(
      (string) $byType['statistical_district']['origin_id'],
      $names,
    );
  }

}
