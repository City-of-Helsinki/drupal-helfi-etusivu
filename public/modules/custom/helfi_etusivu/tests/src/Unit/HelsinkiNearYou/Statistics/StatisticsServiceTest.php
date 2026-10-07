<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\HelsinkiNearYou\Statistics;

use Drupal\Tests\UnitTestCase;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_api_base\ServiceMap\DTO\StreetName;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\District;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Figure;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DistrictResolverInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsClientInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsService;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsService
 * @group helfi_etusivu
 */
class StatisticsServiceTest extends UnitTestCase {

  /**
   * Tests that a resolved district yields every dataset.
   *
   * @covers ::getStatistics
   */
  public function testGetStatistics() : void {
    $client = $this->createMock(StatisticsClientInterface::class);
    $client->method('getPopulation')->willReturn($this->figure('population', 9549.0));
    $client->method('getAverageIncome')->willReturn($this->figure('income', 51671.0));
    $client->method('getDwellings')->willReturn($this->figure('dwellings', 5938.0));

    $collection = $this->getSut($client, $this->district())
      ->getStatistics($this->address());

    $this->assertSame('0913301102', $collection->district->code);
    $this->assertSame('Fiskehamnen', $collection->district->getName('sv'));
    $this->assertSame(['population', 'income', 'dwellings'], array_keys($collection->figures));
    $this->assertSame(9549.0, $collection->figures['population']->value);
    $this->assertSame(51671.0, $collection->figures['income']->value);
    $this->assertSame(5938.0, $collection->figures['dwellings']->value);
  }

  /**
   * Tests that one failing dataset does not lose the others.
   *
   * @covers ::getStatistics
   */
  public function testPartialFailure() : void {
    $client = $this->createMock(StatisticsClientInterface::class);
    $client->method('getPopulation')->willReturn($this->figure('population', 9549.0));
    $client->method('getAverageIncome')
      ->willThrowException(new StatisticsException('Connection timed out'));
    $client->method('getDwellings')->willReturn($this->figure('dwellings', 5938.0));

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())->method('log');

    $collection = $this->getSut($client, $this->district(), $logger)
      ->getStatistics($this->address());

    $this->assertSame(['population', 'dwellings'], array_keys($collection->figures));
  }

  /**
   * Tests that coordinates outside every district yield NULL.
   *
   * The client must not be called at all: without a district there is no area
   * code to query with.
   *
   * @covers ::getStatistics
   */
  public function testUnresolvedDistrict() : void {
    $client = $this->createMock(StatisticsClientInterface::class);
    $client->expects($this->never())->method('getPopulation');

    $this->assertNull($this->getSut($client, NULL)->getStatistics($this->address()));
  }

  /**
   * Constructs the service under test.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsClientInterface $client
   *   The statistics client.
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\District|null $district
   *   The district the resolver returns.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   The logger.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsService
   *   The service.
   */
  private function getSut(
    StatisticsClientInterface $client,
    ?District $district,
    ?LoggerInterface $logger = NULL,
  ) : StatisticsService {
    $resolver = $this->createMock(DistrictResolverInterface::class);
    $resolver->method('resolve')->willReturn($district);

    return new StatisticsService(
      $resolver,
      $client,
      $logger ?? $this->createMock(LoggerInterface::class),
    );
  }

  /**
   * Builds a figure.
   *
   * @param string $key
   *   The machine name.
   * @param float|null $value
   *   The value.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Figure
   *   The figure.
   */
  private function figure(string $key, ?float $value) : Figure {
    return new Figure($key, $key, $value);
  }

  /**
   * Builds a district.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\District
   *   The district.
   */
  private function district() : District {
    return District::create('0913301102', ['fi' => 'Kalasatama', 'sv' => 'Fiskehamnen']);
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

}
