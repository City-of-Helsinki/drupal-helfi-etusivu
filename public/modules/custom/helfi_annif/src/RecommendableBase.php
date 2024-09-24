<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\node\Entity\Node;

/**
 * Base class for recommendations.
 */
abstract class RecommendableBase extends Node implements RecommendableInterface {

  protected const SHOW_RECOMMENDATIONS_BLOCK = 'show_annif_block';

  /**
   * {@inheritDoc}
   */
  public function isRecommendableContent(): bool {
    return !$this->get(TopicsManager::TOPICS_FIELD)->isEmpty();
  }

  /**
   * {@inheritDoc}
   */
  public function isBlockSetVisible(): bool {
    return (bool) $this->get(self::SHOW_RECOMMENDATIONS_BLOCK)->value;
  }

  /**
   * {@inheritDoc}
   */
  public function showRecommendationsBlock(): bool {
    return $this->hasKeywords() &&
      $this->isBlockSetVisible();
  }

  /**
   * {@inheritDoc}
   */
  public function hasKeywords(): bool {
    $field = $this->getTopicsField();

    foreach ($field->referencedEntities() as $topics) {
      assert($topics instanceof SuggestedTopicsInterface);
      if ($topics->hasKeywords()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheTagsToInvalidate(): array {
    $parentCacheTags = parent::getCacheTagsToInvalidate();
    if (!$this->hasField(TopicsManager::TOPICS_FIELD)) {
      return $parentCacheTags;
    }

    $keywordsCacheTags = $this->getKeywordsCacheTags();
    return Cache::mergeTags($parentCacheTags, $keywordsCacheTags);
  }

  /**
   * Get the cache tags for all the keywords.
   *
   * @return array
   *   Array of cache tags for keywords.
   */
  protected function getKeywordsCacheTags(): array {
    $field = $this->getTopicsField();

    $tags = array_map(
      fn ($term) => $term->getCacheTags(),
      $field->referencedEntities()
    );
    return array_merge(...$tags);
  }

  /**
   * {@inheritDoc}
   */
  public function getTopicsField(): EntityReferenceFieldItemListInterface {
    $field = $this->get(TopicsManager::TOPICS_FIELD);
    assert($field instanceof EntityReferenceFieldItemListInterface);

    return $field;
  }

}
