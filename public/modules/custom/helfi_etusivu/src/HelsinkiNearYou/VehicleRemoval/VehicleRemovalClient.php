<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval;

use Drupal\helfi_api_base\Environment\EnvironmentResolver;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval\DTO\Item;
use Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval\DTO\MobileNoteResult;
use GuzzleHttp\ClientInterface;

/**
 * A lazy builder for Vehicle removal data block.
 */
final readonly class VehicleRemovalClient {

  const string API_ENDPOINT = '/api/v1/mobilenote';

  public function __construct(
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
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval\DTO\MobileNoteResult
   *   The mobobile note data items.
   */
  public function getMobileNoteData(Address $address, int $limit = 3): MobileNoteResult {
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

    $response = $this->httpClient->request('GET', $url,
      [
        'query' => $query,
        'limit' => $limit,
        'timeout' => 30,
      ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);
    $mobileNoteItems = [];
    foreach ($data['results'] as $vehicleRemovalItem) {
      $validFrom = $vehicleRemovalItem['_source']['valid_from'][0];
      $validTo = $vehicleRemovalItem['_source']['valid_to'][0];
      $tz = new \DateTimeZone('Europe/Helsinki');

      $validFrom = new \DateTimeImmutable("@$validFrom", $tz);
      $validTo = new \DateTimeImmutable("@$validTo", $tz);

      $mobileNoteItems[] = new Item(
        $vehicleRemovalItem['_source']['address'][0],
        $vehicleRemovalItem['_source']['address'][0],
        $this->createDateRange($validFrom, $validTo),
        $vehicleRemovalItem['_source']['map_url'][0],
        $vehicleRemovalItem['_source']['time_range'][0],
      );
    }

    $postalCodes = isset($data['postalcodes']) ? explode(',', $data['postalcodes']) : NULL;
    return new MobileNoteResult(
      $data['parkingzone'] ?? NULL,
      $postalCodes,
      $mobileNoteItems,
    );
  }

  /**
   * Create the date range string.
   *
   * @param \DateTimeImmutable $from
   *   The from date.
   * @param \DateTimeImmutable $to
   *   The to date.
   *
   * @return string
   *   The date range string.
   */
  private function createDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): string {
    // Same date, return the to-date.
    if ($from->format('j.n.Y') === $to->format('j.n.Y')) {
      return $to->format('j.n.Y');
    }

    // Different year, return all.
    if ($from->format('Y') !== $to->format('Y')) {
      return $from->format('j.n.Y') . '-' . $to->format('j.n.Y');
    }

    // Different month.
    if ($from->format('n') !== $to->format('n')) {
      return $from->format('j.n.') . '-' . $to->format('j.n.Y');
    }

    // Different day.
    if ($from->format('j') !== $to->format('j')) {
      return $from->format('j.') . '-' . $to->format('j.n.Y');
    }

    return $from->format('j.n.Y') . '-' . $to->format('j.n.Y');
  }

}
