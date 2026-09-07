<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Shared save behaviour for entity forms.
 */
trait SearchEntityFormTrait {

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  #[\Override]
  public function save(array $form, FormStateInterface $form_state): int {
    $saved = parent::save($form, $form_state);
    $entity = $this->getEntity();

    $options = [
      '@type' => $entity->getEntityType()->getLabel(),
      '%label' => $entity->hasLinkTemplate('canonical')
        ? $entity->toLink()->toString()
        : $entity->label(),
    ];

    $this
      ->messenger()
      ->addStatus($saved === SAVED_NEW
        ? $this->t('@type %label has been created.', $options)
        : $this->t('@type %label has been updated.', $options),
      );

    // Redirect the user to the overview page.
    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $saved;
  }

}
