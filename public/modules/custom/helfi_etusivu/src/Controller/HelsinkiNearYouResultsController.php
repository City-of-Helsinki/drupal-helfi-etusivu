<?php

namespace Drupal\helfi_etusivu\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Helsinki near you controller.
 */
class HelsinkiNearYouResultsController extends ControllerBase {

  /**
   * Returns a renderable array.
   */
  public function content() : array {
    $build = [
      '#markup' => $this->t('Helsinki near you'),
    ];

    return $build;
  }

}
