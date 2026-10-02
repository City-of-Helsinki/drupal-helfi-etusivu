<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Search form for Feedback page.
 */
class FeedbackSearchForm extends SearchFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getRedirectRoute(): string {
    return 'helfi_etusivu.helsinki_near_you_feedback';
  }

  /**
   * The parent validateForm causes white screen.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
  }

}
