<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedback;

use Drupal\Component\Utility\UrlHelper;
use Drupal\helfi_etusivu\HelsinkiNearYou\Distance;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Collection;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Feedback;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Request;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * Fetches and processes Feedback data from the Open311 API.
 */
final readonly class Client {

  /**
   * Constructs a new instance.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   */
  public function __construct(
    private ClientInterface $httpClient,
  ) {
  }

  /**
   * Constructs a URI for given request object.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Request $request
   *   The request object.
   *
   * @return string
   *   The constructed URI.
   */
  private function getUri(Request $request) : string {
    $uri = 'https://palautteet.hel.fi/public-api/open311-public-service/v1/requests.json';

    $query = [
      'extensions' => 1,
      'lat' => $request->lat,
      'long' => $request->lon,
      // Radius in kilometers.
      'radius' => $request->radius,
    ];

    // @todo Start date filter is broken at the moment.
    if ($request->start_date) {
      // The date must be exactly in 2024-05-01T12:00:00Z format.
      $query['start_date'] = vsprintf('%sT%sZ', [
        $request->start_date->format('Y-m-d'),
        $request->start_date->format('H:i:s'),
      ]);
    }
    return sprintf('%s?%s', $uri, UrlHelper::buildQuery($query));
  }

  /**
   * Fetches results from the API.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Request $request
   *   The request object.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Collection
   *   An array of feedback items.
   */
  public function get(Request $request) : Collection {
    $numItems = 0;
    $map = [];

    try {
      $data = $this->httpClient->request('GET', $this->getUri($request), [
        RequestOptions::TIMEOUT => 10,
      ]);

      // Calculate distance and sort by it.
      $items = array_map(function (array $item) use ($request) {
        $item['distance'] = 0;

        if (isset($item['lat'], $item['long'])) {
          $item['distance'] = Distance::calculateDistance(
            $request->lat,
            $request->lon,
            (float) $item['lat'],
            (float) $item['long'],
          );
        }
        return $item;

      }, json_decode($data->getBody()->getContents(), TRUE) ?? []);

      usort($items, fn (array $a, array $b) => $a['distance'] <=> $b['distance']);

      if ($items) {
        $numItems = count($items);

        if ($request->limit) {
          $items = array_slice($items, $request->offset, $request->limit);
        }
      }

      foreach ($items as $item) {
        try {
          $map[] = Feedback::createFromArray($item);
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
