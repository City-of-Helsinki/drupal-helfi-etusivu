<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the search suggestion entity.
 */
final class SuggestionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  #[\Override]
  public function save(array $form, FormStateInterface $form_state): int {
    $saved = parent::save($form, $form_state);
    $entity = $this->getEntity();

    $this->messenger()->addStatus(
      $saved === SAVED_NEW
        ? $this->t('Search suggestion %label has been created.', ['%label' => $entity->label()], ['context' => 'Helfi search'])
        : $this->t('Search suggestion %label has been updated.', ['%label' => $entity->label()], ['context' => 'Helfi search'])
    );

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $saved;
  }

}
