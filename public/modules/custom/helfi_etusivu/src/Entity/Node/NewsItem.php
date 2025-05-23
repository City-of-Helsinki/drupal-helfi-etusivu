<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Node;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\radioactivity\RadioactivityInterface;
use Drupal\node\Entity\Node;

/**
 * A bundle class for NewsItem -node.
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

        // PHPStan does not like $date property:
        // https://www.drupal.org/project/drupal/issues/3425302.
        // @phpstan-ignore-next-line
        $updateDate = $latest->get('field_news_update_date')->date;

        /**  @var \Drupal\Core\Datetime\DrupalDateTime $updateDate */
        $this->set('published_at', $updateDate->getTimestamp());

        // Reset radioactivity.
        $this->resetRadioactivity();
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
  public function getNewsUpdates() : array {
    $field = $this->get('field_news_item_updating_news');
    assert($field instanceof EntityReferenceFieldItemListInterface);
    return $field->referencedEntities();
  }

  /**
   * Resets radioactivity field.
   */
  private function resetRadioactivity() : void {
    $radioactivity = $this->get('field_radioactivity');
    assert($radioactivity instanceof EntityReferenceFieldItemListInterface);

    $requestTime = \Drupal::time()->getRequestTime();
    $defaultEnergy = $radioactivity->getFieldDefinition()->getSetting('default_energy') ?? 0.0;

    foreach ($radioactivity->referencedEntities() as $entity) {
      assert($entity instanceof RadioactivityInterface);

      $entity->setEnergy($defaultEnergy);
      $entity->setTimestamp($requestTime);
      $entity->save();
    }
  }

  /**
   * Gets the first updating news items publish date timestamp.
   *
   * @return int|null
   *   The timestamp or null.
   */
  public function getFirstUpdatingNewsPublishDate() : ?int {
    $newsUpdates = $this->getNewsUpdates();

    if ($first = reset($newsUpdates)) {
      assert($first instanceof FieldableEntityInterface);

      // PHPStan does not like $date property:
      // https://www.drupal.org/project/drupal/issues/3425302.
      // @phpstan-ignore-next-line
      $updateDate = $first->get('field_news_update_date')->date->getTimestamp();

      return $updateDate;
    }

    return NULL;
  }

}
