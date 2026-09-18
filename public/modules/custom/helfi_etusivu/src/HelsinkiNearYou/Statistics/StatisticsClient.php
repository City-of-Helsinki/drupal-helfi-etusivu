<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Statistics;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_api_base\ApiClient\ApiClient;
use Drupal\helfi_api_base\ApiClient\CacheValue;
use Drupal\helfi_api_base\Cache\CacheKeyTrait;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Figure;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Fetches neighbourhood statistics from the Aluesarjat PxWeb API.
 *
 * Only the Finnish database is queried: the sv and en trees do not contain
 * these tables and respond with HTTP 400. Values are numbers, and labels are
 * mapped from the PxWeb variable values rather than read from the response.
 */
final readonly class StatisticsClient implements StatisticsClientInterface {

  use CacheKeyTrait;
  use DecodedResponseTrait;

  private const BASE_URL = 'https://stat.hel.fi/api/v1/fi/Aluesarjat/';

  private const TABLE_POPULATION = 'vrm/ennak/alu_ennak_001b.px';

  private const TABLE_INCOME = 'tul/vatul/alu_vatul_011r.px';

  private const TABLE_DWELLINGS = 'asu/askan/alu_askan_005l.px';

  /**
   * Response cache lifetime.
   *
   * The source data changes quarterly at most, and the API's rate limits are
   * undocumented.
   */
  private const TTL = 86400;

  private const CONTEXT = ['context' => 'Helsinki near you'];

  public function __construct(
    #[Autowire(service: 'helfi_etusivu.statistics_api_client')]
    private ApiClient $client,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getPopulation(string $areaCode) : Figure {
    $period = $this->getLatestPeriod(self::TABLE_POPULATION, 'Neljännesvuosi');

    $dataset = $this->query(self::TABLE_POPULATION, [
      $this->selection('Osa-alue', [$areaCode]),
      $this->selection('Ikä', ['all']),
      $this->selection('Tiedot', ['enn']),
      $this->selection('Neljännesvuosi', [$period]),
    ]);

    return new Figure(
      key: 'population',
      label: new TranslatableMarkup('Preliminary population', [], self::CONTEXT),
      value: $dataset->value(),
      unit: new TranslatableMarkup('people', [], self::CONTEXT),
      period: $period,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAverageIncome(string $areaCode) : Figure {
    $period = $this->getLatestPeriod(self::TABLE_INCOME, 'Vuosi');

    $dataset = $this->query(self::TABLE_INCOME, [
      $this->selection('Alue', [$areaCode]),
      $this->selection('Vuosi', [$period]),
      $this->selection('Tiedot', ['valtve_ka']),
    ]);

    return new Figure(
      key: 'income',
      label: new TranslatableMarkup('State-taxable income, average', [], self::CONTEXT),
      value: $dataset->value(),
      unit: new TranslatableMarkup('euros', [], self::CONTEXT),
      period: $period,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDwellings(string $areaCode) : Figure {
    $period = $this->getLatestPeriod(self::TABLE_DWELLINGS, 'Vuosi');

    $dataset = $this->query(self::TABLE_DWELLINGS, [
      $this->selection('Alue', [$areaCode]),
      $this->selection('Vuosi', [$period]),
      // 'ALL' is the variable's own total, so totals never have to be summed
      // from cells that may be suppressed.
      $this->selection('Talotyyppi', ['ALL', '1', '2', '3', '4']),
      $this->selection('Valmistumisvuosi', ['*'], filter: 'all'),
    ]);

    $types = $dataset->categories('Talotyyppi');
    $years = $dataset->categories('Valmistumisvuosi');
    $unit = new TranslatableMarkup('dwellings', [], self::CONTEXT);

    $total = NULL;
    $breakdown = [];

    foreach ($types as $typeIndex => $typeKey) {
      $byYear = [];
      $typeTotal = NULL;

      foreach ($years as $yearIndex => $yearKey) {
        $value = $dataset->value([
          'Talotyyppi' => $typeIndex,
          'Valmistumisvuosi' => $yearIndex,
        ]);

        if ($yearKey === 'ALL') {
          $typeTotal = $value;
          continue;
        }

        $byYear[] = new Figure(
          key: $yearKey,
          label: $this->completionYearLabel($yearKey, $dataset->label('Valmistumisvuosi', $yearKey)),
          value: $value,
          unit: $unit,
          period: $period,
        );
      }

      if ($typeKey === 'ALL') {
        $total = $typeTotal;
        continue;
      }

      $breakdown[] = new Figure(
        key: $typeKey,
        label: $this->buildingTypeLabel($typeKey, $dataset->label('Talotyyppi', $typeKey)),
        value: $typeTotal,
        unit: $unit,
        period: $period,
        breakdown: $byYear,
      );
    }

    return new Figure(
      key: 'dwellings',
      label: new TranslatableMarkup('Dwellings by building type and completion year', [], self::CONTEXT),
      value: $total,
      unit: $unit,
      period: $period,
      breakdown: $breakdown,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestPeriod(string $table, string $variable) : string {
    $key = $this->getCacheKey('helfi_etusivu:statistics:meta', [$table]);
    $data = $this->request($key, fn () => $this->client->makeRequest('GET', self::BASE_URL . $table));

    foreach ($data['variables'] ?? [] as $candidate) {
      if (($candidate['code'] ?? NULL) !== $variable) {
        continue;
      }
      $values = $candidate['values'] ?? [];

      if ($latest = end($values)) {
        return (string) $latest;
      }
    }

    throw new StatisticsException(
      sprintf('Table "%s" has no values for period variable "%s".', $table, $variable)
    );
  }

  /**
   * Runs a json-stat2 query against a table.
   *
   * @param string $table
   *   The table path.
   * @param array $query
   *   The query selections.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\JsonStat2
   *   The dataset.
   *
   * @throws \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException
   *   Thrown when the request fails.
   */
  private function query(string $table, array $query) : JsonStat2 {
    $body = [
      'query' => $query,
      'response' => ['format' => 'json-stat2'],
    ];
    $key = $this->getCacheKey('helfi_etusivu:statistics:query', [$table, $query]);

    return new JsonStat2($this->request($key, fn () => $this->client->makeRequest(
      'POST',
      self::BASE_URL . $table,
      [RequestOptions::JSON => $body],
    )));
  }

  /**
   * Performs a cached request.
   *
   * @param string $key
   *   The cache key.
   * @param callable $callback
   *   Callback returning an ApiResponse.
   *
   * @return array
   *   The decoded response.
   *
   * @throws \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException
   *   Thrown when the request fails.
   */
  private function request(string $key, callable $callback) : array {
    try {
      $value = $this->client->cache($key, fn () => new CacheValue(
        $callback(),
        $this->client->cacheMaxAge(self::TTL),
        ['helfi_etusivu_statistics'],
      ));
    }
    catch (GuzzleException | \JsonException $e) {
      throw new StatisticsException($e->getMessage(), previous: $e);
    }

    return $value ? $this->toArray($value->response) : [];
  }

  /**
   * Builds one PxWeb query selection.
   *
   * @param string $code
   *   The variable code.
   * @param array $values
   *   The selected values.
   * @param string $filter
   *   The PxWeb filter, 'item' or 'all'.
   *
   * @return array
   *   The selection.
   */
  private function selection(string $code, array $values, string $filter = 'item') : array {
    return [
      'code' => $code,
      'selection' => ['filter' => $filter, 'values' => $values],
    ];
  }

  /**
   * Returns the label for a building type.
   *
   * @param string $key
   *   The Talotyyppi value.
   * @param string|null $fallback
   *   The dataset's own label, used if the variable gains a new value.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label.
   */
  private function buildingTypeLabel(string $key, ?string $fallback) : string|TranslatableMarkup {
    // The strings have to be literals here, or they are not extractable for
    // translation.
    return match ($key) {
      '1' => new TranslatableMarkup('Detached and semi-detached houses', [], self::CONTEXT),
      '2' => new TranslatableMarkup('Terraced houses', [], self::CONTEXT),
      '3' => new TranslatableMarkup('Blocks of flats', [], self::CONTEXT),
      '4' => new TranslatableMarkup('Other buildings', [], self::CONTEXT),
      default => $fallback ?? $key,
    };
  }

  /**
   * Returns the label for a completion year bucket.
   *
   * @param string $key
   *   The Valmistumisvuosi value.
   * @param string|null $fallback
   *   The dataset's own label.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label. Year ranges read the same in every language and are used as
   *   they come.
   */
  private function completionYearLabel(string $key, ?string $fallback) : string|TranslatableMarkup {
    // '9999' is the bucket for an unknown completion year.
    if ($key === '9999') {
      return new TranslatableMarkup('Unknown', [], self::CONTEXT);
    }

    return $fallback ?? $key;
  }

}
