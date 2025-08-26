<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents;

use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO\Collection;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO\Event;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * Fetches and processes Feedback data from the Open311 API.
 */
final readonly class Client {

  public function __construct(
    private LinkedEvents $linkedEvents,
    private ClientInterface $httpClient,
  ) {
  }

  /**
   * Fetches results from the API.
   *
   * @param array $options
   *   The query options.
   * @param string $langcode
   *   The langcode.
   * @param int|null $limit
   *   The number of items to show.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO\Collection
   *   A collection of events.
   */
  public function get(array $options, string $langcode, ?int $limit = NULL) : Collection {
    $numItems = 0;
    $map = [];

    try {
      $data = $this->httpClient->request('GET', $this->linkedEvents->getEventsRequest($langcode, $options, $limit), [
        RequestOptions::TIMEOUT => 10,
      ]);
      $json = json_decode($data->getBody()->getContents(), TRUE);

      if (isset($json['meta']['count'])) {
        $numItems = (int) $json['meta']['count'];
      }

      foreach ($json['data'] ?? [] as $item) {
        try {
          $map[] = Event::createFromArray($langcode, $item);
        }
        catch (\InvalidArgumentException) {
          continue;
        }
      }
    }
    catch (GuzzleException) {
    }

    return new Collection($numItems, $map);
  }

}
