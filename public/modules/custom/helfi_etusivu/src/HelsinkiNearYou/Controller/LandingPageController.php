<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Enum\LandingPageImage;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\LandingPageSearchForm;
use Drupal\helfi_etusivu\HelsinkiNearYou\LandingPageImages;

/**
 * Helsinki near you controller.
 *
 * The liftup and banners are hard-coded in the page template. This controller
 * provides the liftup image as media, rendered the same way as in the
 * corresponding paragraph. The images are selected in
 * \Drupal\helfi_etusivu\HelsinkiNearYou\Form\LandingPageImagesForm.
 *
 * The hero is rendered by
 * \Drupal\helfi_etusivu\Plugin\Block\HelsinkiNearYouHeroBlock.
 */
final class LandingPageController extends ControllerBase implements ContainerInjectionInterface {

  use AutowireTrait;

  /**
   * Constructs a new instance.
   */
  public function __construct(
    protected readonly FileUrlGeneratorInterface $fileUrlGenerator,
    protected readonly ThemeHandlerInterface $themeHandler,
    protected readonly LandingPageImages $landingPageImages,
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
      '#illustration_url_1x' => $path . 'walking_by_houses-513x513.png',
      '#illustration_url_2x' => $path . 'walking_by_houses-1026x1026.png',
      '#illustration_caption' => 'Lille Santanen',
      '#search_form' => $this->formBuilder()->getForm(LandingPageSearchForm::class),
      '#cache' => [
        'tags' => [LandingPageImages::CACHE_TAG],
      ],
    ];

    // Liftup: image on right. Same media view mode and responsive image style
    // as liftup with image paragraphs, see
    // hdbt_preprocess_paragraph__liftup_with_image().
    if ($liftupImage = $this->landingPageImages->getMedia(LandingPageImage::Liftup)) {
      $build['#liftup_image'] = $this->landingPageImages->buildMediaImage($liftupImage, 'image', 'liftup_left_right');
      if ($photographer = $this->landingPageImages->getPhotographer($liftupImage)) {
        $build['#liftup_image_caption'] = [
          'caption_identifier_class' => 'image__caption',
          'credit' => $photographer,
        ];
      }
    }

    return $build;
  }

}
