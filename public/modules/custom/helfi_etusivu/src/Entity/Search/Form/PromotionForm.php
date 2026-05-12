<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the promotion edit forms.
 */
class PromotionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   *
   * @phpstan-return array<string, mixed>
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Promotion is not revisionable, so ContentEntityForm does not create the
    // 'advanced' vertical-tabs group. Add it here so scheduler fields (and the
    // Gin sidebar layout) have a group to attach to.
    if (!isset($form['advanced'])) {
      $form['advanced'] = [
        '#type' => 'vertical_tabs',
        '#weight' => 99,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $saved = parent::save($form, $form_state);

    $options = [
      '@type' => $this->getEntity()->getEntityType()->getLabel(),
      '%label' => $this->getEntity()->toLink()->toString(),
    ];

    $this
      ->messenger()
      ->addStatus($saved === SAVED_NEW
        ? $this->t('@type %label has been created.', $options)
        : $this->t('@type %label has been updated.', $options),
      );

    // Redirect the user to the overview page.
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));

    return $saved;
  }

}
