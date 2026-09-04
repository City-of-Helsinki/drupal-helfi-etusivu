<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Translation handler for the helfi search entities.
 *
 * @see \Drupal\helfi_etusivu\Entity\Search\SuggestionTranslationHandler
 * @see \Drupal\node\NodeTranslationHandler
 */
class SearchTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<array-key, mixed> $form
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
   *
   * @phpstan-param array<string, mixed> $form
   */
  #[\Override]
  public function entityFormEntityBuild($entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state): void {
    if ($form_state->hasValue('content_translation') && $entity instanceof EntityPublishedInterface) {
      $translation = &$form_state->getValue('content_translation');
      $translation['status'] = $entity->isPublished();
    }
    parent::entityFormEntityBuild($entity_type, $entity, $form, $form_state);
  }

}
