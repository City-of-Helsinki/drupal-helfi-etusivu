<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Form;

/**
 * Search form for Feedbacks page.
 */
class FeedbacksSearchForm extends SearchFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getRedirectRoute(): string {
    return 'helfi_etusivu.helsinki_near_you_feedbacks';
  }

}
