<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks;

use Drupal\Component\Utility\UrlHelper;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\DTO\Feedback;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\DTO\Request;
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
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\DTO\Request $request
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
    if ($request->limit) {
      $query['limit'] = $request->limit;
    }

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
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\DTO\Request $request
   *   The request object.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\DTO\Feedback[]
   *   An array of feedback items.
   */
  public function get(Request $request) : array {
    try {
      $data = $this->httpClient->request('GET', $this->getUri($request), [
        RequestOptions::TIMEOUT => 10,
      ]);
      $json = json_decode($data->getBody()->getContents(), TRUE);

      $map = [];
      foreach ($json as $item) {
        try {
          $map[] = Feedback::createFromArray($item);
        }
        catch (\InvalidArgumentException) {
          continue;
        }
      }
      return $map;
    }
    catch (GuzzleException) {
    }
    return [];
  }

}
