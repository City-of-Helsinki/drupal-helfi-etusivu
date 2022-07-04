<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the Example add and edit forms.
 */
class GlobalMenuForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    return parent::form($form, $form_state);
  }

}
