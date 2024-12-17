<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;

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
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node.
   * @param int $limit
   *   How many recommendations should be returned.
   * @param string|null $target_langcode
   *   Which translation to use to select the recommendations,
   *   null uses the entity's translation.
   *
   * @return array
   *   Array of recommendations.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRecommendations(EntityInterface $entity, int $limit = 3, ?string $target_langcode = NULL): array {
    $destination_langcode = $entity->language()->getId();
    $target_langcode = $target_langcode ?? $destination_langcode;
    if ($entity instanceof TranslatableInterface && !$entity->hasTranslation($target_langcode)) {
      $target_langcode = $destination_langcode;
    }

    $queryResult = $this->executeQuery($entity, $target_langcode, $destination_langcode, $limit);
    if (!$queryResult || !is_array($queryResult)) {
      return [];
    }

    $this->sortByCreatedAt($queryResult);
    $nids = array_column($queryResult, 'nid');

    $entities = $this->entityManager
      ->getStorage($entity->getEntityTypeId())
      ->loadMultiple($nids);

    // Entity query returns the results sorted by nid in ascending order
    // while the raw query's results are in correct order.
    $entities = $this->sortEntitiesByQueryResult($entities, $queryResult);

    return $this->getTranslations($entities, $destination_langcode);
  }

  /**
   * Execute the recommendation query.
   *
   * The recommendations can be unified between the translations
   * by always getting the results using the primary language recommendations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity we want to suggest recommendations for.
   * @param string $target_langcode
   *   What language are we using as a base for the recommendations.
   * @param string $destination_langcode
   *   What is the destination langcode.
   * @param int $limit
   *   How many items to get.
   *
   * @return array
   *   Database query result.
   */
  private function executeQuery(EntityInterface $entity, string $target_langcode, string $destination_langcode, int $limit): array {
    $query = "
      SELECT
        n.nid,
        count(n.nid) as relevancy,
        nfd.created,
        nfd.status
      FROM node as n
      INNER JOIN node__annif_suggested_topics as reference ON n.nid = reference.entity_id
      INNER JOIN suggested_topics as topics ON topics.id = reference.annif_suggested_topics_target_id
      INNER JOIN suggested_topics__keywords as keywords ON topics.id = keywords.entity_id
      INNER JOIN node_field_data as nfd ON nfd.nid = n.nid AND nfd.langcode = :destination_langcode
      WHERE
        nfd.status = 1
        -- Select rows that have keywords in common with current entity.
        AND keywords.keywords_target_id IN
          (SELECT keywords_target_id
           FROM suggested_topics__keywords stk
           INNER JOIN node__annif_suggested_topics AS node ON stk.entity_id = node.annif_suggested_topics_target_id
           WHERE node.entity_id = :nid)
        -- Filter out entities that should be hidden from recommendations.
        AND n.nid NOT IN
          (SELECT DISTINCT restriction.entity_id
           FROM node__in_recommendations as restriction
           WHERE restriction.in_recommendations_value = 0)
        AND n.langcode = :target_langcode
        AND n.nid != :nid
        AND nfd.created > :timestamp
      GROUP BY n.nid
      ORDER BY count(n.nid) DESC
      LIMIT {$limit};
    ";

    // Cannot add :limit as parameter here,
    // must be added directly to the query string above.
    return $this->connection
      ->query($query, [
        ':nid' => $entity->id(),
        ':target_langcode' => $target_langcode,
        ':destination_langcode' => $destination_langcode,
        ':timestamp' => strtotime("-1 year", time()),
      ])
      ->fetchAll();
  }

  /**
   * Sort query result by created time.
   *
   * @param array $results
   *   Query results to sort.
   */
  private function sortByCreatedAt(array &$results) : void {
    usort($results, function ($a, $b) {
      if ($a->created == $b->created) {
        return 0;
      }
      return ($a->created > $b->created) ? -1 : 1;
    });
  }

  /**
   * Entity query changes the result sorting, it must be corrected afterward.
   *
   * @param array $entities
   *   Array of entities sorted by id.
   * @param array $queryResult
   *   Array of query results sorted correctly.
   *
   * @return array
   *   Correctly sorted array of entities.
   */
  private function sortEntitiesByQueryResult(array $entities, array $queryResult) : array {
    $results = [];
    foreach ($queryResult as $result) {
      if (!isset($entities[$result->nid])) {
        continue;
      }
      $results[] = $entities[$result->nid];
    }
    return $results;
  }

  /**
   * Get the translations for the recommended entities.
   *
   * @param array $entities
   *   Array of entities.
   * @param string $destination_langcode
   *   Which translation to get.
   *
   * @return array
   *   Array of translated entities.
   */
  private function getTranslations(array $entities, string $destination_langcode) : array {
    $results = [];
    foreach ($entities as $entity) {
      if ($entity instanceof TranslatableInterface && $entity->hasTranslation($destination_langcode)) {
        $results[] = $entity->getTranslation($destination_langcode);
      }
    }
    return $results;
  }

}
