<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Form;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Search form for near you page.
 */
class LandingPageSearchForm extends SearchFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getRedirectRoute(): string {
    return 'helfi_etusivu.helsinki_near_you_results';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSearchLandmarkLabel(): TranslatableMarkup {
    return $this->t('Helsinki near you', [], ['context' => 'Helsinki near you search aria-label']);
  }

}
