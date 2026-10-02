<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Search form for Roadwork page.
 */
class RoadworkSearchForm extends SearchFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getRedirectRoute(): string {
    return 'helfi_etusivu.helsinki_near_you_roadworks';
  }

  /**
   * The parent validateForm causes white screen.
   *
   * @param array $form
   *   The form.
   * @param FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
  }

}
