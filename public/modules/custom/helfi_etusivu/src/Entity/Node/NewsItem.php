<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Node;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\node\Entity\Node;

/**
 * A bundle class for node entities.
 */
final class NewsItem extends Node {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) : void {
    if ($this->isPublished()) {
      $newsUpdates = $this->getNewsUpdates();

      // Copy published_at field from latest news update.
      if ($latest = end($newsUpdates)) {
        assert($latest instanceof FieldableEntityInterface);
        /**  @var \Drupal\Core\Datetime\DrupalDateTime $updateDate */
        $updateDate = $latest->get('field_news_update_date')->date;

        // Replace published_at with update date from latest news update.
        $this->set('published_at', $updateDate->getTimestamp());
      }
    }

    parent::preSave($storage);
  }

  /**
   * Get news updates.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   News updates entities.
   */
  private function getNewsUpdates() : array {
    $field = $this->get('field_news_item_updating_news');
    assert($field instanceof EntityReferenceFieldItemListInterface);
    return $field->referencedEntities();
  }

}
