<?php

namespace Drupal\helfi_migration\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;


/**
 * A Drush command file.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class MigrationCommands extends DrushCommands {

  const ATOM_NEWS_SOURCE_URL = "https://helfirest-hki-kanslia-aok-drupal-nodered.agw.arodevtest.hel.fi/helfirest/Category/fi/wcmrest%3A1c4e68f6-84c4-42b7-a126-dc98dd7db2d3?pageSize=1000&page=1";

  /**
   * Import Atom news.
   *
   * @command import:atom-news
   * @aliases import-atom-news
   */
  public function importAtomNews() {
    $method = 'GET';
    $options = [];

    $client = \Drupal::httpClient();

    $response = $client->request($method, self::ATOM_NEWS_SOURCE_URL, $options);
    $code = $response->getStatusCode();
    if ($code == 200) {
      $xmlString = $response->getBody()->getContents();
      $xmlObject = new \SimpleXMLElement($xmlString);
      $articles = $xmlObject->xpath('/atom:feed/atom:entry');
      foreach ($articles as $article) {
        $atomProps = $article->children('atom', TRUE);
        $wcmProps = $article->children('wcm', TRUE);
        $node = $this->getArticleNode((string) $atomProps->id);
      }
    }
  }

  private function getArticleNode(string $atomId): Node {
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $query->condition('type', 'news_item');
    $query->condition('field_atomid', $atomId);
    $nids = $query->execute();
    if (empty($nids)) {
      return Node::create();
    } else {
      if (count($nids) === 1) {
        return Node::load($nids[0]);
      } else {
        throw new \Exception("More than 1 article found for the same Atom ID!");
      }
    }
  }

}
