<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'suggested_topics_reference' field type.
 */
#[FieldType(
  id: "suggested_topics_reference",
  label: new TranslatableMarkup("Annif recommendations"),
  description: new TranslatableMarkup("This field stores the ID and settings related to suggested topics."),
  category: "reference",
  default_widget: "suggested_topics_reference",
  default_formatter: "entity_reference_label",
  list_class: EntityReferenceFieldItemList::class,
)]
final class SuggestedTopicsReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings(): array {
    return [
      'target_type' => 'suggested_topics',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data): array {
    $elements = parent::storageSettingsForm($form, $form_state, $has_data);

    $elements['target_type']['#access'] = FALSE;

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::fieldSettingsForm($form, $form_state);

    $form['handler']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update): bool {
    /** @var \Drupal\helfi_annif\Entity\SuggestedTopics $entity */
    $parent = $this->getEntity();
    $this->entity
      ->set('parent_id', $parent->id())
      ->save();

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(): void {
    if ($this->entity) {
      $this->entity->delete();
    }
  }

}
