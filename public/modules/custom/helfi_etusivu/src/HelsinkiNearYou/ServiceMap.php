<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Location;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\StreetName;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Class for interacting with Servicemap API.
 */
final class ServiceMap implements ServiceMapInterface {

  use StringTranslationTrait;

  /**
   * API URL for querying data.
   *
   * @var string
   */
  private const API_URL = 'https://api.hel.fi/servicemap/v2/search/';

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
   * {@inheritdoc}
   */
  public function getAddressData(string $address) : ?Address {
    $results = $this->query($address);

    if ($item = reset($results)) {
      return $item;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function query(string $address, int $page_size = 1) : array {
    $address = Xss::filter($address);

    try {
      $response = $this->client->request('GET', self::API_URL, [
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

    $result = json_decode($response->getBody()->getContents(), TRUE);

    if (!isset($result['results'])) {
      return [];
    }

    return array_map(function (array $result) {
      return new Address(
        StreetName::createFromArray($result['name']),
        Location::createFromArray($result['location']),
      );
    }, $result['results']);
  }

}
