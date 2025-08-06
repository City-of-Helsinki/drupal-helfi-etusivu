<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Helsinki near you controller.
 */
final class LandingPageController extends ControllerBase implements ContainerInjectionInterface {

  use AutowireTrait;

  /**
   * Constructs a new instance.
   */
  public function __construct(
    protected readonly FileUrlGeneratorInterface $fileUrlGenerator,
    protected readonly ThemeHandlerInterface $themeHandler,
  ) {
  }

  /**
   * Returns a renderable array.
   */
  public function content() : array {
    $theme_path = $this->themeHandler
      ->getTheme('hdbt_subtheme')
      ->getPath();

    // Add theme path to as variable.
    $path = $this->fileUrlGenerator
      ->generate("$theme_path/src/images/")
      ->toString(TRUE)->getGeneratedUrl();

    $build = [
      '#theme' => 'helsinki_near_you_landing_page',
      '#title' => $this->t('Start by entering your street address', [], ['context' => 'Helsinki near you']),
      '#description' => $this->t('Enter your street address in the search field above to find services, events and news related to your neighbourhood.', [], ['context' => 'Helsinki near you']),
      '#illustration_url_1x' => $path . 'walking_by_houses-513x513.png',
      '#illustration_url_2x' => $path . 'walking_by_houses-1026x1026.png',
      '#illustration_caption' => $this->t('Picture: Lille Santanen', [], ['context' => 'Helsinki near you']),
    ];

    return $build;
  }

}
