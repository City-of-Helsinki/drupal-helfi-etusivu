<?php /** @noinspection SqlDialectInspection */

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
   * @param EntityTypeManagerInterface $entityManager
   * @param Connection $connection
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityManager,
    private readonly Connection $connection
  ) {
  }

  /**
   * Get recommendations for a node.
   *
   * @param EntityInterface $node
   *   The node
   *
   * @return array
   *   Array of recommendations
   */
  public function getRecommendations(EntityInterface $node): array {

    $query = "
      select
         n.nid,
         count(n.nid) as relevancy
      from node as n
      left join node__field_annif_keywords as annif on n.nid = annif.entity_id
      where annif.field_annif_keywords_target_id in
         (select
          field_annif_keywords_target_id
          from node__field_annif_keywords
          where entity_id = :nid and
          langcode = 'fi')
      and n.langcode = 'fi'
      and annif.langcode = 'fi'
      and n.nid != :nid
      group by n.nid
      order by relevancy DESC
      limit 4;
    ";

    $results = $this->connection->query($query, [':nid' => $node->id()])->fetchAll();

    if (!$results || !is_array($results)) {
      return [];
    }

    $nids = array_column($results, 'nid');

    try {
      $entities = $this->entityManager
        ->getStorage($node->getEntityTypeId())
        ->loadMultiple($nids);
    }
    catch(\Exception $e) {
      $this->logger->error($e->getMessage());
      return [];
    }

    return $entities;
  }

}
