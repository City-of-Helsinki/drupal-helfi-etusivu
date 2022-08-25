<?php

declare(strict_types = 1);

namespace Drupal\helfi_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\source\Url;
use Drupal\migrate\Row;

/**
 * Source plugin for newsitem content.
 *
 * @MigrateSource(
 *   id = "newsitem_node"
 * )
 */
final class NewsitemNode extends Url {

  const ATOM_NEWS_SOURCE_URL = 'https://helfirest-hki-kanslia-aok-drupal-nodered.agw.arodevtest.hel.fi/helfirest/Content/';

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

  private function populateUrlList(array &$urlList, string $url) {
    $method = 'GET';
    $options = [];

    $client = \Drupal::httpClient();
    $page = 1;
    while (TRUE) {
      $pageUrl = $url . "?pageSize=100&page=" . $page;
      dump ($pageUrl);
      $response = $client->request($method, $pageUrl, $options);
      $code = $response->getStatusCode();
      if ($code == 200) {
        $xmlString = $response->getBody()->getContents();
        $xmlObject = new \SimpleXMLElement($xmlString);
        $contentIds = $xmlObject->xpath('/atom:feed/atom:entry/atom:id');
        foreach ($contentIds as $contentId) {
          $urlList[] = self::ATOM_NEWS_SOURCE_URL . ((string)$contentId);
        }
      }
      if (count($contentIds) < 100) {
        break;
      }
      $page++;
    }
//    die();
  }

  private function getAtomNews(string $id) {
    $method = 'GET';
    $options = [];

    $client = \Drupal::httpClient();

    $response = $client->request($method, self::ATOM_NEWS_SOURCE_URL . $id, $options);
    $code = $response->getStatusCode();
    if ($code == 200) {
      $xmlString = $response->getBody()->getContents();
      $xmlObject = new \SimpleXMLElement($xmlString);
      $articles = $xmlObject->xpath('/atom:feed/atom:entry');
      foreach ($articles as $article) {
        $atomProps = $article->children('atom', TRUE);
        $wcmProps = $article->children('wcm', TRUE);
      }
    }
  }

}
