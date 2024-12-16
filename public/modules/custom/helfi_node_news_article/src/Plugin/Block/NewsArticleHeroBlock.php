<?php

declare(strict_types=1);

namespace Drupal\helfi_node_news_article\Plugin\Block;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\helfi_node_news_article\Entity\Node\NewsArticle;
use Drupal\helfi_platform_config\Plugin\Block\ContentBlockBase;

/**
 * Provides a 'NewsArticleHeroBlock' block.
 *
 * @Block(
 *  id = "news_article_hero_block",
 *  admin_label = @Translation("News article hero block"),
 * )
 */
class NewsArticleHeroBlock extends ContentBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() : array {
    $build = [];

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    ['entity' => $entity] = $this->getCurrentEntityVersion();

    // No need to continue if current entity is not an instance of News article.
    if (!$entity instanceof NewsArticle) {
      return $build;
    }

    // No need to continue if current entity doesn't have hero field.
    if (
      !$entity instanceof ContentEntityInterface ||
      $entity->bundle() !== 'news_article'
    ) {
      return $build;
    }

    $image_display_options = [
      'label' => 'hidden',
      'type' => 'responsive_image',
      'settings' => [
        'responsive_image_style' => $this->getHeroDesign($entity),
        'image_link' => '',
        'image_loading' => [
          'attribute' => 'eager',
        ],
      ],
    ];

    /** @var \Drupal\Core\Entity\Plugin\DataType\EntityReference $entity_reference */
    $entity_reference = $entity->get('field_main_image')
      ?->first()
      ?->get('entity');

    /** @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $entity_adapter */
    $entity_adapter = $entity_reference?->getTarget();

    /** @var \Drupal\media\Entity\Media $media */
    $media = $entity_adapter?->getEntity();

    // Render array of the image.
    $image = $media
      ?->get('field_media_image')
      ?->first()
      ?->view($image_display_options);

    $image_author = '';

    $image_author_name = $media
      ?->get('field_photographer')
      ?->first()
      ?->getString();

    if (!empty($image_author_name)) {
      $image_author = $this->t(
        'Image: @image_author',
        ['@image_author' => $image_author_name],
        ['context' => 'Helfi Paragraphs Hero']
      );
    }

    $build['news_article_hero_block'] = [
      '#theme' => 'news_article_hero_block',
      '#title' => $entity->label(),
      '#description' => $entity->get('field_lead_in')?->first()?->view(),
      '#design' => $entity->get('field_hero_design')?->first()?->getString(),
      '#image' => $image,
      '#image_author' => $image_author,
      '#published_time' => $entity->getPublishedHumanReadable(),
      '#html_published_time' => $entity->getPublishedMachineReadable(),
      '#updated_time' => $entity->getUpdatedHumanReadable(),
      '#html_updated_time' => $entity->getUpdatedMachineReadable(),
      '#cache' => [
        'tags' => $entity->getCacheTags(),
      ],
    ];

    return $build;
  }

  /**
   * Get field hero design value and return responsive image style as a string.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content entity.
   *
   * @return string
   *   Return responsive image style as a string.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function getHeroDesign(ContentEntityInterface $entity): string {
    return match ($entity->get('field_hero_design')?->first()?->getString()) {
      'with-image-right', 'with-image-left' => 'hero__left_right',
      'with-image-bottom' => 'hero__bottom',
      default => 'hero__diagonal',
    };
  }

}
