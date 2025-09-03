<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents;

use Drupal\Core\Url;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO\Collection;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO\Event;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * Fetches and processes Feedback data from the Open311 API.
 */
final readonly class Client {

  public const BASE_URL = 'https://tapahtumat.hel.fi';
  public const API_URL = 'https://api.hel.fi/linkedevents/v1/';
  public const HOBBIES_BASE_URL = 'https://harrastukset.hel.fi';

  public function __construct(private ClientInterface $httpClient) {
  }

  /**
   * Form url for getting events from api.
   *
   * @param string $langcode
   *   The language code.
   * @param array $options
   *   Filters as key = value array.
   * @param int $pageSize
   *   How many events to load in a page.
   *
   * @return string
   *   Resulting api url with params a query string
   */
  public function getUri(string $langcode, array $options, int $pageSize) : string {
    $defaultOptions = [
      'event_type' => 'General',
      'format' => 'json',
      'include' => 'keywords,location',
      'page' => 1,
      'page_size' => $pageSize,
      'sort' => 'end_time',
      'start' => 'now',
      'super_event_type' => 'umbrella,none',
      'language' => $langcode,
    ];

    $options = array_merge($defaultOptions, $options);

    if (!isset($options['all_ongoing_AND'])) {
      $options['all_ongoing'] = 'true';
    }

    // Linked events URLs should end with '/' (URLs without '/' are redirect).
    return Url::fromUri(self::API_URL . 'event/', options: ['query' => $options])->toString();
  }

  /**
   * Fetches results from the API.
   *
   * @param array $options
   *   The query options.
   * @param string $langcode
   *   The langcode.
   * @param int $limit
   *   The number of items to show.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO\Collection
   *   A collection of events.
   */
  public function get(array $options, string $langcode, int $limit) : Collection {
    $numItems = 0;
    $map = [];

    try {
      $data = $this->httpClient->request('GET', $this->getUri($langcode, $options, $limit), [
        RequestOptions::TIMEOUT => 10,
      ]);
      $json = json_decode($data->getBody()->getContents(), TRUE);

      if (isset($json['meta']['count'])) {
        $numItems = (int) $json['meta']['count'];
      }

      foreach ($json['data'] ?? [] as $item) {
        $map[] = Event::createFromArray($langcode, $item);
      }
    }
    catch (GuzzleException) {
    }

    return new Collection($numItems, $map);
  }

}
