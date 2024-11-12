<?php

namespace Drupal\helfi_etusivu\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Helsinki near you controller.
 */
class HelsinkiNearYouController extends ControllerBase {

  /**
   * Returns a renderable array.
   */
  public function content() : array {
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $service */
    $service = \Drupal::service('file_url_generator');
    $theme = \Drupal::service('theme_handler')->getTheme('hdbt_subtheme');

    // Add theme path to as variable.
    $path = $service->generate("{$theme->getPath()}/src/images/")
      ->toString(TRUE)->getGeneratedUrl();

    $build = [
      '#theme' => 'helsinki_near_you_landing_page',
      '#illustration' => $path . 'photographer.svg',
    ];

    return $build;
  }

}
