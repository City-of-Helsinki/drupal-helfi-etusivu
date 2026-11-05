<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\HelsinkiNearYou\Statistics;

use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsClient;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException;
use GuzzleHttp\Psr7\Response;

/**
 * @coversDefaultClass \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsClient
 * @group helfi_etusivu
 */
class StatisticsClientTest extends StatisticsTestBase {

  /**
   * Tests that the latest period is read from table metadata.
   *
   * Hardcoding a period would go stale every quarter, and a table that names
   * no period at all is an error rather than a silent default.
   *
   * @covers ::getLatestPeriod
   */
  public function testGetLatestPeriod() : void {
    $http = $this->createMockHttpClient([
      new Response(200, body: json_encode(
        $this->metadata('Neljännesvuosi', ['2025Q4', '2026Q1', '2026Q2']),
        JSON_THROW_ON_ERROR,
      )),
    ]);
    $sut = new StatisticsClient($this->getApiClient($http));

    $this->assertSame('2026Q2', $sut->getLatestPeriod('vrm/ennak/alu_ennak_001b.px', 'Neljännesvuosi'));

    $http = $this->createMockHttpClient([
      new Response(200, body: json_encode($this->metadata('Vuosi', ['2024']), JSON_THROW_ON_ERROR)),
    ]);
    $sut = new StatisticsClient($this->getApiClient($http));

    $this->expectException(StatisticsException::class);
    $sut->getLatestPeriod('tul/vatul/alu_vatul_011r.px', 'Neljännesvuosi');
  }

  /**
   * Tests the two single value datasets.
   *
   * Labels are our own English source strings, so the Finnish source labels
   * never reach a template.
   *
   * @covers ::getPopulation
   * @covers ::getAverageIncome
   */
  public function testSingleValueFigures() : void {
    $http = $this->createMockHttpClient([
      new Response(200, body: json_encode($this->metadata('Neljännesvuosi', ['2026Q2']), JSON_THROW_ON_ERROR)),
      new Response(200, body: json_encode($this->singleValueDataset(7465), JSON_THROW_ON_ERROR)),
    ]);
    $population = (new StatisticsClient($this->getApiClient($http)))->getPopulation('0911101010');

    $this->assertSame('population', $population->key);
    $this->assertSame(7465.0, $population->value);
    $this->assertSame('2026Q2', $population->period);
    $this->assertTrue($population->hasValue());
    $this->assertSame('Preliminary population', $this->untranslated($population->label));
    $this->assertSame('people', $this->untranslated($population->unit));

    $http = $this->createMockHttpClient([
      new Response(200, body: json_encode($this->metadata('Vuosi', ['2023', '2024']), JSON_THROW_ON_ERROR)),
      new Response(200, body: json_encode($this->singleValueDataset(66895.8), JSON_THROW_ON_ERROR)),
    ]);
    $income = (new StatisticsClient($this->getApiClient($http)))->getAverageIncome('0911101010');

    $this->assertSame('income', $income->key);
    $this->assertSame(66895.8, $income->value);
    $this->assertSame('2024', $income->period);
    $this->assertSame('State-taxable income, average', $this->untranslated($income->label));
    $this->assertSame('euros', $this->untranslated($income->unit));

    // A suppressed cell must read as "no data", not as zero.
    $http = $this->createMockHttpClient([
      new Response(200, body: json_encode($this->metadata('Neljännesvuosi', ['2026Q2']), JSON_THROW_ON_ERROR)),
      new Response(200, body: json_encode($this->singleValueDataset(NULL), JSON_THROW_ON_ERROR)),
    ]);
    $suppressed = (new StatisticsClient($this->getApiClient($http)))->getPopulation('0911101010');

    $this->assertNull($suppressed->value);
    $this->assertFalse($suppressed->hasValue());
  }

  /**
   * Tests the dwellings dataset and its two level breakdown.
   *
   * The dataset is read in both dimension orders, which guards the wiring
   * between this class and the cube reader.
   *
   * @covers ::getDwellings
   */
  public function testGetDwellings() : void {
    foreach ([FALSE, TRUE] as $transposed) {
      $case = $transposed ? 'transposed' : 'row major';
      $http = $this->createMockHttpClient([
        new Response(200, body: json_encode($this->metadata('Vuosi', ['2025']), JSON_THROW_ON_ERROR)),
        new Response(200, body: json_encode($this->dwellingsDataset($transposed), JSON_THROW_ON_ERROR)),
      ]);
      $figure = (new StatisticsClient($this->getApiClient($http)))->getDwellings('0913301102');

      // The 'ALL' row is the total and must not appear as a building type.
      $this->assertSame(5938.0, $figure->value, $case);
      $this->assertCount(4, $figure->breakdown, $case);

      $types = array_combine(
        array_map(fn ($item) => $this->untranslated($item->label), $figure->breakdown),
        array_map(fn ($item) => $item->value, $figure->breakdown),
      );
      // Building types are mapped from the stable PxWeb values to our own
      // English source strings, not passed through from the Finnish API.
      $this->assertSame([
        'Detached and semi-detached houses' => 11.0,
        'Terraced houses' => 25.0,
        'Blocks of flats' => 5898.0,
        'Other buildings' => 4.0,
      ], $types, $case);

      $flats = $figure->breakdown[2];
      $this->assertCount(2, $flats->breakdown, $case);
      // Year ranges read the same in every language, so they are kept as they
      // come from the dataset.
      $this->assertSame('2010 - 2019', $flats->breakdown[0]->label, $case);
      $this->assertSame(2056.0, $flats->breakdown[0]->value, $case);
      $this->assertSame(3842.0, $flats->breakdown[1]->value, $case);
    }

    // The unknown bucket's own label is the Finnish 'Tuntematon'.
    $dataset = $this->dwellingsDataset();
    $dataset['dimension']['Valmistumisvuosi']['category'] = [
      'index' => ['ALL' => 0, '9999' => 1],
      'label' => ['ALL' => 'Yhteensä', '9999' => 'Tuntematon'],
    ];
    $dataset['size'] = [1, 5, 2, 1];
    $dataset['value'] = [5938, 0, 11, 0, 25, 0, 5898, 0, 4, 0];

    $http = $this->createMockHttpClient([
      new Response(200, body: json_encode($this->metadata('Vuosi', ['2025']), JSON_THROW_ON_ERROR)),
      new Response(200, body: json_encode($dataset, JSON_THROW_ON_ERROR)),
    ]);
    $bucket = (new StatisticsClient($this->getApiClient($http)))
      ->getDwellings('0913301102')->breakdown[0]->breakdown[0];

    $this->assertSame('9999', $bucket->key);
    $this->assertSame('Unknown', $this->untranslated($bucket->label));
  }

  /**
   * Tests that a failing request is wrapped in a StatisticsException.
   *
   * @covers ::getPopulation
   */
  public function testRequestFailure() : void {
    $http = $this->createMockHttpClient([new Response(500)]);
    $sut = new StatisticsClient($this->getApiClient($http));

    $this->expectException(StatisticsException::class);
    $sut->getPopulation('0911101010');
  }

  /**
   * Builds a dwellings dataset.
   *
   * Building types: ALL, 1, 2, 3, 4. Completion years: ALL, 2010-2019, 2020-.
   *
   * @param bool $transposed
   *   Whether to declare Valmistumisvuosi before Talotyyppi.
   *
   * @return array<mixed>
   *   The json-stat2 dataset.
   */
  private function dwellingsDataset(bool $transposed = FALSE) : array {
    // Rows are building types, columns are completion years.
    $matrix = [
      [5938, 2078, 3856],
      [11, 11, 0],
      [25, 11, 14],
      [5898, 2056, 3842],
      [4, 0, 0],
    ];

    $dimensions = [
      'Talotyyppi' => [
        'category' => [
          'index' => ['ALL' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4],
          'label' => [
            'ALL' => 'Yhteensä',
            '1' => 'Omakoti- ja paritalot',
            '2' => 'Rivitalot',
            '3' => 'Kerrostalot',
            '4' => 'Muut rakennukset',
          ],
        ],
      ],
      'Valmistumisvuosi' => [
        'category' => [
          'index' => ['ALL' => 0, '2010 - 2019' => 1, '2020 -' => 2],
          'label' => [
            'ALL' => 'Yhteensä',
            '2010 - 2019' => '2010 - 2019',
            '2020 -' => '2020 -',
          ],
        ],
      ],
    ];

    if (!$transposed) {
      return [
        'id' => ['Alue', 'Talotyyppi', 'Valmistumisvuosi', 'Vuosi'],
        'size' => [1, 5, 3, 1],
        'dimension' => $dimensions,
        'value' => array_merge(...$matrix),
      ];
    }

    $values = [];
    foreach (array_keys($matrix[0]) as $column) {
      foreach ($matrix as $row) {
        $values[] = $row[$column];
      }
    }

    return [
      'id' => ['Alue', 'Valmistumisvuosi', 'Talotyyppi', 'Vuosi'],
      'size' => [1, 3, 5, 1],
      'dimension' => $dimensions,
      'value' => $values,
    ];
  }

}
