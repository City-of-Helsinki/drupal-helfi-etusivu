<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\Statistics;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\helfi_api_base\Traits\EnvironmentResolverTrait;
use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_api_base\ServiceMap\DTO\StreetName;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsServiceInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Figure;
use GuzzleHttp\Psr7\Response;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the container-wired statistics service against mocked responses.
 *
 * The service's own branching is covered by the unit test; this asserts that
 * the wiring holds and that a real response shape maps all the way through.
 *
 * @group helfi_etusivu
 */
class StatisticsServiceTest extends KernelTestBase {

  use ProphecyTrait;
  use ApiTestTrait;
  use EnvironmentResolverTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_api_base',
    'diff',
    'helfi_etusivu',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    // ApiClient reads the active environment to decide on SSL verification.
    $this->setActiveProject(Project::ETUSIVU, EnvironmentEnum::Test);
  }

  /**
   * Tests resolving an address into every figure.
   */
  public function testGetStatistics() : void {
    $client = $this->createMockHttpClient([
      $this->districtResponse(),
      $this->metadataResponse('Neljännesvuosi', ['2026Q2']),
      $this->valueResponse(9549),
      $this->metadataResponse('Vuosi', ['2024']),
      $this->valueResponse(51671),
      $this->metadataResponse('Vuosi', ['2025']),
      $this->dwellingsResponse(),
    ]);
    $this->container->set('http_client', $client);

    $collection = $this->container->get(StatisticsServiceInterface::class)
      ->getStatistics($this->address());

    $this->assertSame('0913301102', $collection->district->code);
    $this->assertSame('Kalasatama', $collection->district->getName('fi'));
    $this->assertSame('Fiskehamnen', $collection->district->getName('sv'));

    $this->assertSame(['population', 'income', 'dwellings'], array_keys($collection->figures));
    $this->assertSame(9549.0, $collection->figures['population']->value);
    $this->assertSame('2026Q2', $collection->figures['population']->period);
    $this->assertSame(51671.0, $collection->figures['income']->value);

    $dwellings = $collection->figures['dwellings'];
    $this->assertSame(5938.0, $dwellings->value);
    $this->assertSame(
      ['Detached and semi-detached houses', 'Terraced houses', 'Blocks of flats', 'Other buildings'],
      array_map(
        fn (Figure $figure) => $this->untranslated($figure->label),
        $dwellings->breakdown,
      ),
    );
    $this->assertSame(5898.0, $dwellings->breakdown[2]->value);
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
  private function untranslated(string|TranslatableMarkup|null $label) : string {
    $this->assertInstanceOf(TranslatableMarkup::class, $label);

    return $label->getUntranslatedString();
  }

  /**
   * Builds an address.
   *
   * @return \Drupal\helfi_api_base\ServiceMap\DTO\Address
   *   The address.
   */
  private function address() : Address {
    return new Address(
      new StreetName('Kalasatamankatu 1', 'Kalasatamankatu 1', 'Kalasatamankatu 1'),
      new Location(60.1862694, 24.9790037, 'Point'),
    );
  }

  /**
   * Builds a district lookup response.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The response.
   */
  private function districtResponse() : Response {
    return new Response(200, body: json_encode([
      'count' => 2,
      'results' => [
        [
          'origin_id' => '102',
          'type' => 'sub_district',
          'name' => ['fi' => 'Kalasatama', 'sv' => 'Fiskehamnen'],
        ],
        [
          'origin_id' => '0913301102',
          'type' => 'statistical_district',
          'name' => ['fi' => 'Kalasatama'],
        ],
      ],
    ], JSON_THROW_ON_ERROR));
  }

  /**
   * Builds a table metadata response.
   *
   * @param string $variable
   *   The period variable.
   * @param array<string> $values
   *   The period values.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The response.
   */
  private function metadataResponse(string $variable, array $values) : Response {
    return new Response(200, body: json_encode([
      'title' => 'Test table',
      'variables' => [['code' => $variable, 'text' => $variable, 'values' => $values]],
    ], JSON_THROW_ON_ERROR));
  }

  /**
   * Builds a single cell dataset response.
   *
   * @param mixed $value
   *   The value.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The response.
   */
  private function valueResponse(mixed $value) : Response {
    return new Response(200, body: json_encode([
      'id' => ['Alue', 'Vuosi', 'Tiedot'],
      'size' => [1, 1, 1],
      'value' => [$value],
    ], JSON_THROW_ON_ERROR));
  }

  /**
   * Builds a dwellings dataset response.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The response.
   */
  private function dwellingsResponse() : Response {
    return new Response(200, body: json_encode([
      'id' => ['Alue', 'Talotyyppi', 'Valmistumisvuosi', 'Vuosi'],
      'size' => [1, 5, 2, 1],
      'dimension' => [
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
            'index' => ['ALL' => 0, '2020 -' => 1],
            'label' => ['ALL' => 'Yhteensä', '2020 -' => '2020 -'],
          ],
        ],
      ],
      'value' => [
        5938, 3856,
        11, 0,
        25, 14,
        5898, 3842,
        4, 0,
      ],
    ], JSON_THROW_ON_ERROR));
  }

}
