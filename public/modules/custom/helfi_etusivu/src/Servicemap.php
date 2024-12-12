<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\Enum\ServiceMapLink;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Class for interacting with Servicemap API.
 */
final class Servicemap {

  /**
   * API URL for querying data.
   *
   * @var string
   */
  private const API_URL = 'https://api.hel.fi/servicemap/v2/search/';
  /**
   * Site url for redirecting users.
   *
   * @var string
   */
  private const SITE_URL = 'https://kartta.hel.fi/';


  /**
   * Constructs a new instance.
   *
   * @param \GuzzleHttp\Client $client
   *   The HTTP client.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   Language manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(
    private readonly ClientInterface $client,
    private readonly LanguageManagerInterface $languageManager,
    #[Autowire(service: 'logger.channel.helfi_etusivu')]
    private readonly LoggerInterface $logger,
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
   *   Array of results.
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
          'language' => $this->languageManager->getCurrentLanguage()->getId(),
        ],
      ]);
    }
    catch (GuzzleException $e) {
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

  /**
   * Generate link to servicemap view with predefined data visible.
   *
   * @param ServiceMapLink
   * @param string $address
   * @return string
   */
  public function getLink(ServiceMapLink $link, string $address) : string {
    $url = Url::fromUri(
      self::SITE_URL,
      ['query' => [
        'link' => $link->link(),
        'address' => Xss::filter($address),
      ]],
    );

    return $url->toString();
  }
}
