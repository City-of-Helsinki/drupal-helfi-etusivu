<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\helfi_api_base\Environment\EnvironmentResolver;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval\DTO\Item;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * A lazy builder for Vehicle removal data block.
 */
final readonly class VehicleRemovalClient implements TrustedCallbackInterface {

  const string API_ENDPOINT = '/api/v1/mobilenote';

  public function __construct(
    #[Autowire(service: 'logger.channel.helfi_etusivu')]
    private readonly LoggerInterface $logger,
    private readonly ClientInterface $httpClient,
    private readonly EnvironmentResolver $environmentResolver,
  ) {
  }

  /**
   * Get the mobile note data from kymp.
   *
   * @param \Drupal\helfi_api_base\ServiceMap\DTO\Address $address
   *   The address.
   * @param int $limit
   *   The limit.
   *
   * @return array<mixed>
   *   The mobobile note data items.
   */
  public function getMobileNoteData(Address $address, int $limit = 3): array {
    $query = [
      'streetname' => $address->streetName->fi,
      'lon' => $address->location->lon,
      'lat' => $address->location->lat,
    ];

    /** @var \Drupal\helfi_api_base\Environment\Environment $environment */
    $environment = $this->environmentResolver->getEnvironment(
      Project::LIIKENNE,
      $this->environmentResolver->getActiveEnvironmentName(),
    );
    $url = $environment->getInternalAddress('fi') . self::API_ENDPOINT;

    try {
      $response = $this->httpClient->request('GET', $url,
        [
          'query' => $query,
          'limit' => $limit,
          'timeout' => 30,
        ]);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return [];
    }

    $data = json_decode($response->getBody()->getContents(), TRUE);

    $resultSet = [];
    foreach ($data as $vehicleRemovalItem) {
      $validFrom = date('d.m.Y', $vehicleRemovalItem['_source']['valid_from'][0]);
      $validTo = date('d.m.Y', $vehicleRemovalItem['_source']['valid_to'][0]);

      $resultSet[] = new Item(
        $vehicleRemovalItem['_source']['address'][0],
        $vehicleRemovalItem['_source']['address'][0],
        "$validFrom - $validTo",
        $vehicleRemovalItem['_source']['map_url'][0],
        $vehicleRemovalItem['_source']['time_range'][0],
      );
    }
    return $resultSet;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() : array {
    return ['build'];
  }

}
