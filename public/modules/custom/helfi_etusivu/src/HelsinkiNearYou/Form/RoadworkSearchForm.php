<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Form;

use Drupal\Core\StringTranslation\TranslatableMarkup;

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
   * {@inheritdoc}
   */
  protected function getSearchLandmarkLabel(): TranslatableMarkup {
    return $this->t('Search for street and park work', [], ['context' => 'Helsinki near you roadworks search']);
  }

}
