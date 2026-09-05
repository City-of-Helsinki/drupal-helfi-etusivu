<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Translation handler for search suggestions.
 */
final class SuggestionTranslationHandler extends SearchTranslationHandler {

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<array-key, mixed> $form
   */
  #[\Override]
  public function entityFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity): void {
    parent::entityFormAlter($form, $form_state, $entity);

    if (isset($form['content_translation'])) {
      $form['content_translation']['#access'] = FALSE;
    }
  }

}
