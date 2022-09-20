<?php

declare(strict_types = 1);

namespace Drupal\helfi_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\source\Url;

/**
 * Source plugin for newsitem content.
 *
 * @MigrateSource(
 *   id = "newsitem_node"
 * )
 */
final class NewsitemNode extends Url {

  const ATOM_NEWS_SOURCE_URL = 'https://helfirest-hki-kanslia-aok-drupal-nodered.agw.arodevtest.hel.fi/helfirest/Content/';
  const PAGE_SIZE = 100;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    if (!is_array($configuration['urls'])) {
      $configuration['urls'] = [$configuration['urls']];
    }
    $urlList = [];
    foreach ($configuration['urls'] as $url) {
      $this->populateUrlList($urlList, $url);
    }
    $configuration['urls'] = $urlList;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->sourceUrls = $configuration['urls'];
  }

  /**
   * Method to populate the list of URLs to query.
   *
   * @param array $urlList
   *   List of URLs to populate
   * @param string $url
   *   URL to query.
   *
   * @return void
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function populateUrlList(array &$urlList, string $url) {
    $client = \Drupal::httpClient();
    $page = 1;
    while (TRUE) {
      $pageUrl = $url . "?pageSize=" . self::PAGE_SIZE . "&page=" . $page;
      $response = $client->request('GET', $pageUrl, []);
      $code = $response->getStatusCode();
      if ($code == 200) {
        $xmlString = $response->getBody()->getContents();
        $xmlObject = new \SimpleXMLElement($xmlString);
        $contentIds = $xmlObject->xpath('/atom:feed/atom:entry/atom:id');
        foreach ($contentIds as $contentId) {
          $urlList[] = self::ATOM_NEWS_SOURCE_URL . ((string) $contentId);
        }
      }
      if (count($contentIds) < self::PAGE_SIZE) {
        break;
      }
      $page++;
    }
  }

}
