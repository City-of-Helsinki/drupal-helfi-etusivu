<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Form;

use Drupal\Core\StringTranslation\TranslatableMarkup;

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
   * {@inheritdoc}
   */
  protected function getSearchLandmarkLabel(): TranslatableMarkup {
    return $this->t('Find feedback', [], ['context' => 'Helsinki near you title']);
  }

}
