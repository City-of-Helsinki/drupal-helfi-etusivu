<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_annif\SuggestedTopicsInterface;

/**
 * Defines the suggested topics entity class.
 *
 * This entity stores the AI suggested topics with their scores. The background
 * tasks that generate the suggested topics should write to this entity instead
 * of the content itself in order to avoid conflicts.
 *
 * @ContentEntityType(
 *   id = "suggested_topics",
 *   label = @Translation("AI suggested topics for text"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "suggested_topics",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
final class SuggestedTopics extends ContentEntityBase implements SuggestedTopicsInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['keywords'] = BaseFieldDefinition::create('scored_entity_reference')
      ->setLabel(new TranslatableMarkup('Keywords'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
