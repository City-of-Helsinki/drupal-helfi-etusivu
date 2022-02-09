<?php

declare(strict_types=1);

namespace Drupal\helfi_linkedevents\Plugin\migrate\destination;

use Drupal\helfi_api_base\Plugin\migrate\destination\TranslatableEntityBase;
use Drupal\migrate\Row;
use Drupal\helfi_linkedevents\Entity\Event;

/**
 * Provides a destination plugin for Linked Offers offer entities.
 *
 * @MigrateDestination(
 *   id = "linkedevents_offer",
 * )
 */
final class Offers extends TranslatableEntityBase {

  /**
   * {@inheritdoc}
   */
  protected static function getEntityTypeId($plugin_id) {
    return 'linkedevents_offer';
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatableFields(): array {
    return [
      'description' => 'description',
      'info_url' => 'info_url',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(Row $row, array $old_destination_id_values) {
    /** @var \Drupal\helfi_linkedevents\Entity\Offer $entity */
    $entity = parent::getEntity($row, $old_destination_id_values);

    if ($parent_id = $row->getSourceProperty('parent_id')) {
      /** @var \Drupal\helfi_linkedevents\Entity\Event $event */
      $event = Event::load($parent_id);
      $event->addOffer($entity)->save();
    }

    return $entity;
  }

}
