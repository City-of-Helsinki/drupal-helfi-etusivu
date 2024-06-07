<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * The recommendation manager.
 */
class RecommendationManager {

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityManager,
    private readonly Connection $connection,
  ) {
  }

  /**
   * Get recommendations for a node.
   *
   * @param EntityInterface $entity
   *   The node.
   * @param int $limit
   *   How many recommendations should be be returned.
   * @param string|null $target_langcode
   *   Allow sharing the recommendations between all translations.
   *
   * @return array
   *   Array of recommendations.
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getRecommendations(EntityInterface $entity, int $limit = 3, string $target_langcode = null): array {
    $entity_langcode = $entity->language()->getId();
    $target_langcode = $target_langcode ?? $entity_langcode;
    $response = [];

    $results = $this->executeQuery($entity, $target_langcode);
    if (!$results || !is_array($results)) {
      return $response;
    }

    $this->sortByCreatedAt($results);
    $nids = array_column($results, 'nid');

    $entities = $this->entityManager
      ->getStorage($entity->getEntityTypeId())
      ->loadMultiple($nids);

    $results = [];
    foreach($entities as $entity) {
      if ($entity->hasTranslation($entity_langcode)) {
        $results[] = $entity->getTranslation($entity_langcode);
      }
      if (count($results) >= 3) {
        break;
      }
    }

    return $results;
  }

  /**
   * Execute the recommendation query.
   *
   * The recommendations can be unified between the translations
   * by always getting the results using the primary language recommendations.
   *
   * @param EntityInterface $entity
   *   The entity we want to suggest recommendations for.
   * @param string $langcode
   *   Langcode which is used to filter results.
   *
   * @return array
   *   Database query result.
   */
  private function executeQuery(EntityInterface $entity, string $langcode) {
    // @todo #UHF-9964 exclude unwanted keywords
    $query = "
      select
        n.nid,
        count(n.nid) as relevancy,
        nfd.created
      from node as n
      left join node__field_annif_keywords as annif on n.nid = annif.entity_id
      left join node_field_data as nfd on nfd.nid = n.nid
      where annif.field_annif_keywords_target_id in
        (select
         field_annif_keywords_target_id
         from node__field_annif_keywords
         where entity_id = :nid and
         langcode = :langcode)
      and n.langcode = :langcode
      and annif.langcode = :langcode
      and nfd.langcode = :langcode
      and n.nid != :nid
      and nfd.created > :timestamp
      group by n.nid
      order by relevancy DESC
      limit 10;
    ";

    $timestamp = strtotime("-1 year", time());
    return $this->connection
      ->query($query, [
        ':nid' => $entity->id(),
        ':langcode' => $langcode,
        ':timestamp' => $timestamp,
      ])
      ->fetchAll();
  }

  /**
   * Sort results by created time.
   *
   * @param array $results
   *   Entities to sort.
   */
  private function sortByCreatedAt(array &$results) : void {
    usort($results, function ($a, $b) {
      if ($a->created == $b->created) {
        return 0;
      }
      return ($a->created > $b->created) ? -1 : 1;
    });
  }

}
