<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Helsinki near you controller.
 */
class HelsinkiNearYouResultsController extends ControllerBase {

  /**
   * Returns a renderable array.
   */
  public function content() : array {
    return [
      '#theme' => 'helsinki_near_you_results_page',
      '#title' => $this->t('Services, events and news near your address @address', ['@address' => 'ADDRESS_GOES_HERE'], ['context' => 'Helsinki near you']),
      '#back_link_url' => Url::fromRoute('helfi_etusivu.helsinki_near_you'),
      '#back_link_label' => $this->t('Edit address', [], ['context' => 'Helsinki near you']),
    ];
  }

}
