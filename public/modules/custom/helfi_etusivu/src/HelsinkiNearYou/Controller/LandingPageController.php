<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Enum\LandingPageImage;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\LandingPageSearchForm;
use Drupal\helfi_etusivu\HelsinkiNearYou\LandingPageImages;
use Drupal\media\MediaInterface;

/**
 * Helsinki near you controller.
 *
 * The hero, liftup and banners are hard-coded in the page template. This
 * controller provides their images as media, rendered the same way as in the
 * corresponding paragraphs. The images are selected in
 * \Drupal\helfi_etusivu\HelsinkiNearYou\Form\LandingPageImagesForm.
 */
final class LandingPageController extends ControllerBase implements ContainerInjectionInterface {

  use AutowireTrait;

  /**
   * Constructs a new instance.
   */
  public function __construct(
    protected readonly FileUrlGeneratorInterface $fileUrlGenerator,
    protected readonly ThemeHandlerInterface $themeHandler,
    protected readonly EntityRepositoryInterface $entityRepository,
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

    // Hero: diagonal design. Same media view mode and responsive image style
    // as hero paragraphs, see hdbt_preprocess_paragraph__hero().
    if ($heroImage = $this->landingPageImages->getMedia(LandingPageImage::Hero)) {
      $build['#hero_image'] = $this->buildMediaImage($heroImage, 'hero', 'hero__diagonal');
      $build['#hero_image_author'] = $this->getPhotographer($heroImage);
    }

    // Liftup: image on right. Same media view mode and responsive image style
    // as liftup with image paragraphs, see
    // hdbt_preprocess_paragraph__liftup_with_image().
    if ($liftupImage = $this->landingPageImages->getMedia(LandingPageImage::Liftup)) {
      $build['#liftup_image'] = $this->buildMediaImage($liftupImage, 'image', 'liftup_left_right');
      if ($photographer = $this->getPhotographer($liftupImage)) {
        $build['#liftup_image_caption'] = [
          'caption_identifier_class' => 'image__caption',
          'credit' => $photographer,
        ];
      }
    }

    return $build;
  }

  /**
   * Renders an image media like a paragraph's media reference field does.
   *
   * Mirrors _hdbt_set_image_style(): the responsive image style is overridden
   * through the 'altered_image_style' property that
   * hdbt_preprocess_responsive_image_formatter() reads.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The image media.
   * @param string $viewMode
   *   The media view mode used by the paragraph's image field.
   * @param string $responsiveImageStyle
   *   The responsive image style.
   *
   * @return array
   *   The render array.
   *
   * @phpstan-return array<string, mixed>
   */
  protected function buildMediaImage(MediaInterface $media, string $viewMode, string $responsiveImageStyle) : array {
    // Clone the media so that the altered image style of one usage does not
    // leak to another usage of the same media, as rendering happens later.
    $media = clone $this->entityRepository->getTranslationFromContext($media);
    assert($media instanceof MediaInterface);

    if ($media->hasField('field_media_image') && !$media->get('field_media_image')->isEmpty()) {
      // @phpstan-ignore-next-line
      $media->get('field_media_image')->first()->altered_image_style = $responsiveImageStyle;
    }

    $build = $this->entityTypeManager()
      ->getViewBuilder('media')
      ->view($media, $viewMode);

    // The altered image style is not part of the media render cache keys, so
    // don't let this output be cached as the media's generic rendering.
    unset($build['#cache']['keys']);

    return $build;
  }

  /**
   * Gets the photographer of an image media.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The image media.
   *
   * @return string|null
   *   The photographer, or NULL if not set.
   */
  protected function getPhotographer(MediaInterface $media) : ?string {
    $media = $this->entityRepository->getTranslationFromContext($media);

    if (!$media->hasField('field_photographer') || $media->get('field_photographer')->isEmpty()) {
      return NULL;
    }
    return $media->get('field_photographer')->value;
  }

}
