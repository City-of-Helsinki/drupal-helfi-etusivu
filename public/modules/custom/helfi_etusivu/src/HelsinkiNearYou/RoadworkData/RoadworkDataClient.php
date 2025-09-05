<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Utility\Error;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Fetches and processes roadwork data from the Helsinki Open Data API.
 *
 * This service handles communication with the external roadwork data API,
 * including making HTTP requests, error handling, and basic data
 * transformation.
 * It implements RoadworkDataClientInterface to ensure consistent API.
 *
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataClientInterface
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataService
 */
class RoadworkDataClient implements RoadworkDataClientInterface {

  public function __construct(
    protected ClientInterface $httpClient,
    #[Autowire(service: 'logger.channel.helfi_etusivu')]
    protected LoggerChannelInterface $logger,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectsByCoordinates(float $x, float $y, int $distance = 1000): array {
    // Format the current date in YYYY-MM-DD format for the API filter.
    $currentDate = (new \DateTime())->format('Y-m-d');

    // Build the WFS request URL.
    $baseUrl = 'https://kartta.hel.fi/ws/geoserver/avoindata/wfs';
    $query = [
      'service' => 'wfs',
      'version' => '2.0.0',
      'request' => 'GetFeature',
      'typeName' => 'avoindata:Kaivuilmoitus_alue',
      'CQL_FILTER' => sprintf(
        'tyo_paattyy>%s AND DWITHIN(singlegeom,SRID=3879;POINT(%f %f),%d,meters)',
        $currentDate,
        $x,
        $y,
        $distance
      ),
      'outputFormat' => 'application/json',
    ];

    try {
      $response = $this->httpClient->request('GET', $baseUrl, [
        'query' => $query,
        'timeout' => 30,
      ]);

      $responseBody = (string) $response->getBody();
      $data = json_decode($responseBody, TRUE);

      if (!isset($data['features']) || !is_array($data['features'])) {
        $errorMsg = 'Invalid response format from roadworks API';
        if (isset($data['error'])) {
          $errorMsg .= ': ' . print_r($data['error'], TRUE);
        }
        elseif (isset($data['message'])) {
          $errorMsg .= ': ' . $data['message'];
        }
        $this->logger->error($errorMsg);

        return ['features' => [], 'totalFeatures' => 0];
      }

      return [
        'totalFeatures' => $data['totalFeatures'],
        'features' => $data['features'],
      ];
    }
    catch (\Exception $e) {
      Error::logException($this->logger, $e);
    }

    return ['features' => [], 'totalFeatures' => 0];
  }

}
