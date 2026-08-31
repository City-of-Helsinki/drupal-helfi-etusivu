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
 * Content translation's own "This translation is published" checkbox is
 * unwanted. Shared by \Drupal\helfi_etusivu\Entity\Search\Promotion and
 * \Drupal\helfi_etusivu\Entity\Search\Suggestion.
 *
 * For Promotion, which is publishable, the checkbox duplicates the native
 * status widget, so it is hidden and kept in sync with the entity's own
 * published status. Suggestion is not publishable at all, so the checkbox
 * (backed by content_translation's own metadata field) is hidden as
 * meaningless and left at its default.
 *
 * @see \Drupal\helfi_etusivu\Entity\Search\SuggestionTranslationHandler
 * @see \Drupal\node\NodeTranslationHandler
 */
class SearchTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
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
