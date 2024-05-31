<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * The recommendation manager.
 */
class RecommendationManager implements LoggerAwareInterface {

  use LoggerAwareTrait;

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
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node.
   *
   * @return array
   *   Array of recommendations.
   */
  public function getRecommendations(EntityInterface $node): array {
    // @todo #UHF-9964 exclude unwanted keywords and entities and refactor.
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
      and created > :timestamp
      group by n.nid
      order by relevancy DESC
      limit 10;
    ";

    $response = [];
    try {
      $timestamp = strtotime("-1 year", time());
      $results = $this->connection
        ->query($query, [
          ':nid' =>  $node->id(),
          ':langcode' => $node->language()->getId(),
          ':timestamp' => $timestamp
        ])
        ->fetchAll();
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return $response;
    }

    if (!$results || !is_array($results)) {
      return $response;
    }

    // limit results and sort by created timestamp.
    $nids = array_splice($results, 0,3);
    usort($nids, function ($a, $b) {
      if ($a->created == $b->created) {
        return 0;
      }
      return ($a->created > $b->created) ? -1 : 1;
    });
    $nids = array_column($nids, 'nid');

    try {
      $response = $this->entityManager
        ->getStorage($node->getEntityTypeId())
        ->loadMultiple($nids);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return [];
    }

    return $response;
  }

}
