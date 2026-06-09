<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Translation handler for the search promotion entity.
 *
 * Content translation's own "This translation is published" checkbox is
 * redundant. Hide the checkbox and let the native widget own the translation
 * published status.
 *
 * @see \Drupal\node\NodeTranslationHandler
 */
final class PromotionTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function entityFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity): void {
    parent::entityFormAlter($form, $form_state, $entity);

    if (isset($form['content_translation']['status'])) {
      $form['content_translation']['status']['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function entityFormEntityBuild($entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    if ($form_state->hasValue('content_translation')) {
      $translation = &$form_state->getValue('content_translation');
      assert($entity instanceof TranslatableInterface);
      $translation['status'] = $entity->isPublished();
    }
    parent::entityFormEntityBuild($entity_type, $entity, $form, $form_state);
  }

}
