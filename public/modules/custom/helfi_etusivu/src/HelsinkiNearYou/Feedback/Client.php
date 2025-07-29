<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedback;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Feedback;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Request;
use Drupal\views\Plugin\views\display\Feed;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Client\RequestExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Fetches and processes Feedback data from the Helsinki Open Data API.
 *
 * This service handles communication with the external Feedback data API,
 * including making HTTP requests, error handling, and basic data
 * transformation.
 */
final readonly class Client {

  /**
   * Constructs a new RoadworkDataClient instance.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   */
  public function __construct(
    private ClientInterface $httpClient,
    #[Autowire(service: 'logger.channel.helfi_etusivu')] private LoggerChannelInterface $logger,
  ) {
  }

  private function getUri(Request $request) : string {
    $uri = 'https://palautteet.hel.fi/public-api/open311-public-service/v1/requests.json';

    $query = [
      'extensions' => 1,
      'lat' => $request->lat,
      'long' => $request->lon,
      // Radius in kilometers.
      'radius' => $request->radius,
      'locale' => $request->locale,
    ];
    return sprintf('%s?%s', $uri, UrlHelper::buildQuery($query));
  }

  public function get(Request $request) : array {
    try {
      $data = $this->httpClient->request('GET', $this->getUri($request), [
        RequestOptions::TIMEOUT => 10,
      ]);
      $json = json_decode($data->getBody()->getContents(), TRUE);

      return array_map(function (array $item) : Feedback {
        return Feedback::createFromArray($item);
      }, $json);
    }
    catch (RequestExceptionInterface) {
    }
    return [];
  }

}
