<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helsinki near you controller.
 */
final class HelsinkiNearYouController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Constructs a new instance.
   */
  public function __construct(
    protected readonly FileUrlGeneratorInterface $fileUrlGenerator,
    protected readonly ThemeHandlerInterface $themeHandler,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_url_generator'),
      $container->get('theme_handler'),
    );
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
      '#title' => $this->t('First make a search with your address', [], ['context' => 'Helsinki near you']),
      '#description' => $this->t('Fill in your address to the search bar above and find services, events and news near you.', [], ['context' => 'Helsinki near you']),
      '#illustration' => $path . 'photographer.svg',
      '#illustration_caption' => $this->t('Picture: Lille Santanen', [], ['context' => 'Helsinki near you']),
    ];

    return $build;
  }

}
