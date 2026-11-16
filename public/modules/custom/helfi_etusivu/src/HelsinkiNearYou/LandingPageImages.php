<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Enum\LandingPageImage;
use Drupal\media\MediaInterface;

/**
 * Stores the images of the Helsinki near you landing page.
 *
 * The media IDs are stored in state instead of config, because media IDs
 * differ between environments and config is deployed to all of them.
 */
final class LandingPageImages {

  /**
   * Cache tag invalidated when the images change.
   */
  public const string CACHE_TAG = 'helfi_etusivu:helsinki_near_you_images';

  /**
   * The state key.
   */
  private const string STATE_KEY = 'helfi_etusivu.helsinki_near_you_images';

  /**
   * Constructs a new instance.
   */
  public function __construct(
    private readonly StateInterface $state,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    private readonly EntityRepositoryInterface $entityRepository,
  ) {
  }

  /**
   * Gets the media ID of an image.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Enum\LandingPageImage $image
   *   The image.
   *
   * @return int|null
   *   The media ID, or NULL if not set.
   */
  public function getMediaId(LandingPageImage $image): ?int {
    $ids = $this->state->get(self::STATE_KEY, []);
    return isset($ids[$image->value]) ? (int) $ids[$image->value] : NULL;
  }

  /**
   * Gets the media of an image.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Enum\LandingPageImage $image
   *   The image.
   *
   * @return \Drupal\media\MediaInterface|null
   *   The media, or NULL if not set or the media no longer exists.
   */
  public function getMedia(LandingPageImage $image): ?MediaInterface {
    if (!$id = $this->getMediaId($image)) {
      return NULL;
    }
    $media = $this->entityTypeManager->getStorage('media')->load($id);
    return $media instanceof MediaInterface ? $media : NULL;
  }

  /**
   * Sets the media IDs of the images.
   *
   * @param array<string, int|null> $ids
   *   Media IDs keyed by LandingPageImage values. NULL removes the image.
   */
  public function setMediaIds(array $ids): void {
    $stored = $this->state->get(self::STATE_KEY, []);
    foreach (LandingPageImage::cases() as $image) {
      if (array_key_exists($image->value, $ids)) {
        $stored[$image->value] = $ids[$image->value] ? (int) $ids[$image->value] : NULL;
      }
    }
    $this->state->set(self::STATE_KEY, array_filter($stored));
    $this->cacheTagsInvalidator->invalidateTags([self::CACHE_TAG]);
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
  public function buildMediaImage(MediaInterface $media, string $viewMode, string $responsiveImageStyle): array {
    // Clone the media so that the altered image style of one usage does not
    // leak to another usage of the same media, as rendering happens later.
    $media = clone $this->entityRepository->getTranslationFromContext($media);
    assert($media instanceof MediaInterface);

    if ($media->hasField('field_media_image') && !$media->get('field_media_image')->isEmpty()) {
      // @phpstan-ignore-next-line
      $media->get('field_media_image')->first()->altered_image_style = $responsiveImageStyle;
    }

    $build = $this->entityTypeManager
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
  public function getPhotographer(MediaInterface $media): ?string {
    $media = $this->entityRepository->getTranslationFromContext($media);

    if (!$media->hasField('field_photographer') || $media->get('field_photographer')->isEmpty()) {
      return NULL;
    }
    return $media->get('field_photographer')->value;
  }

}
