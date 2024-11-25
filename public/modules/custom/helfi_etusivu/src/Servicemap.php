<?php

namespace Drupal\helfi_etusivu;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Language\LanguageManager;
use Error;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class Servicemap {
  const API_URL = 'https://api.hel.fi/servicemap/v2/search/';

  public function __construct(
    protected readonly Client $client,
    protected readonly LanguageManager $languageManager,
    protected readonly LoggerInterface $logger
  ) {
  }

  /**
   * Queries location data based on address.
   *
   * @param string $address
   *   Address to query against.
   * @param int $page_size
   *   Maximum number or results.
   *
   * @return array
   */
  public function query(string $address, int $page_size = 1) : array {
    $address = Xss::filter($address);

    try {
      $response = $this->client->get(self::API_URL, [
        'query' => [
          'format' => 'json',
          'municipality' => 'helsinki',
          'page_size' => $page_size,
          'q' => $address,
          'type' => 'address',
        ]
      ]);
    } catch (GuzzleException $e) {
      $this->logger->error('Servicemap query failed: ' . $e->getMessage());
      return [];
    }

    $result = json_decode($response->getBody()->getContents());

    if (!isset($result->results)) {
      $this->logger->error('Servicemap query failed: Unexpected response. Results not present.');
      return [];
    }

    return $result->results;
  }

}
