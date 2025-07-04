<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\Enum\ServiceMapLink;
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
   * Site url for redirecting users.
   *
   * @var string
   */
  private const SITE_URL = 'https://kartta.hel.fi/';

  /**
   * Gets the address label for a given service map link.
   *
   * @param \Drupal\helfi_etusivu\Enum\ServiceMapLink $link
   *   The service map link option.
   * @param string $address
   *   The address for which the label is generated.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The address label corresponding to the service map link.
   */
  private function getAddressLabel(ServiceMapLink $link, string $address) : TranslatableMarkup {
    return match($link) {
      ServiceMapLink::ROADWORK_EVENTS => $this->t('Street works and events near the address @address', ['@address' => $address], ['context' => 'Helsinki near you address label']),
      ServiceMapLink::CITYBIKE_STATIONS_STANDS => $this->t('City bike stations and bicycle racks near the address @address', ['@address' => $address], ['context' => 'Helsinki near you address label']),
      ServiceMapLink::STREET_PARK_PROJECTS => $this->t('Street and park projects near the address @address', ['@address' => $address], ['context' => 'Helsinki near you address label']),
      ServiceMapLink::PLANS_IN_PROCESS => $this->t('Plans under preparation near the address @address', ['@address' => $address], ['context' => 'Helsinki near you address label']),
    };
  }

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
  public function getAddressData(string $address) : ?array {
    $results = $this->query($address);

    if (
      isset($results['0']->name) &&
      isset($results['0']->location->coordinates)
    ) {
      return [
        'address_translations' => $results['0']->name,
        'coordinates' => $results['0']->location->coordinates,
      ];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getLink(ServiceMapLink $link, string $address) : string {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $query = [
      'addresslabel' => $this->getAddressLabel($link, $address),
      'addresslocation' => Xss::filter($address),
      'link' => $link->link(),
      'setlanguage' => $langcode,
    ];

    $url = Url::fromUri(
      self::SITE_URL,
      [
        'query' => $query,
      ],
    );

    return $url->toString();
  }

}
