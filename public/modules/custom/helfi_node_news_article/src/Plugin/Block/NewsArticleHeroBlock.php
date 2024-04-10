<?php

declare(strict_types=1);

namespace Drupal\helfi_node_news_article\Plugin\Block;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\helfi_platform_config\EntityVersionMatcher;
use Drupal\helfi_platform_config\Plugin\Block\ContentBlockBase;
use Drupal\paragraphs\ParagraphInterface;

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
    ['entity' => $entity, 'entity_version' => $entity_version] = $this->getCurrentEntityVersion();

    // No need to continue if current entity doesn't have hero field.
    if (
      !$entity instanceof ContentEntityInterface ||
      $entity->bundle() !== 'news_article'
    ) {
      return $build;
    }

    $image_display_options = [
      'label' => 'hidden',
      'type' => 'entity_reference_entity_view',
      'settings' => [
        'view_mode' => 'hero',
      ],
    ];

    $build['news_article_hero_block'] = [
      '#theme' => 'news_article_hero_block',
      '#title' => $entity->label(),
      '#description' => $entity->get('field_lead_in')?->first()?->view(),
      '#design' => $entity->get('field_hero_design')?->first()?->getString(),
      '#image' => $entity->get('field_main_image')?->view($image_display_options),
      '#cache' => [
        'tags' => $entity->getCacheTags(),
      ],
    ];

    return $build;
  }

}
