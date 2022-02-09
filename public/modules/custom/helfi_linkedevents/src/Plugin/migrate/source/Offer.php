<?php

declare(strict_types=1);

namespace Drupal\helfi_linkedevents\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\ContentEntity;

/**
 * Source plugin for retrieving data from Linked Events.
 *
 * @MigrateSource(
 *   id = "linkedevents_offer",
 *   deriver = "\Drupal\migrate_drupal\Plugin\migrate\source\ContentEntityDeriver",
 * )
 */
class Offer extends ContentEntity {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['id' => ['type' => 'string']];
  }

  /**
   * Loads and yields entities, one at a time.
   *
   * @param array $ids
   *   The entity IDs.
   *
   * @return \Generator
   *   An iterable of the loaded entities.
   */
  protected function yieldEntities(array $ids) {
    $storage = $this->entityTypeManager
      ->getStorage($this->entityType->id());
    foreach ($ids as $id) {
      /**
      * @var \Drupal\Core\Entity\ContentEntityInterface $entity
      */
      $entity = $storage->load($id);
      $data = $entity->getData('offer');
      if ($data) {
        $data['id'] = hash('sha256', json_encode($data));
        $data['langcode'] = $entity->language()->getId();
        $data['parent_id'] = $entity->id();
        yield $data;
      }
    }
  }

}
