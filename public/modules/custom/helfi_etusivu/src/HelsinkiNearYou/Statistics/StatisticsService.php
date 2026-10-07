<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Statistics;

use Drupal\Core\Utility\Error;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Collection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Produces neighbourhood statistics for an address.
 *
 * The district is required, the datasets are not: each is fetched on its own
 * so that one failure still leaves the others available.
 */
final readonly class StatisticsService implements StatisticsServiceInterface {

  public function __construct(
    private DistrictResolverInterface $districtResolver,
    private StatisticsClientInterface $client,
    #[Autowire(service: 'logger.channel.helfi_etusivu')]
    private LoggerInterface $logger,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getStatistics(Address $address) : ?Collection {
    $district = $this->districtResolver->resolve($address->location);

    if (!$district) {
      return NULL;
    }

    $datasets = [
      'population' => fn () => $this->client->getPopulation($district->code),
      'income' => fn () => $this->client->getAverageIncome($district->code),
      'dwellings' => fn () => $this->client->getDwellings($district->code),
    ];

    $figures = [];
    foreach ($datasets as $key => $callback) {
      try {
        $figures[$key] = $callback();
      }
      catch (StatisticsException $e) {
        Error::logException($this->logger, $e);
      }
    }

    return new Collection($district, $figures);
  }

}
