<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

use Drupal\Core\Cache\Cache;
use Drupal\node\Entity\Node;

/**
 * Base class for recommendations.
 */
abstract class RecommendableBase extends Node implements RecommendableInterface {

  protected const string KEYWORDFIELD = 'annif_keywords';

  protected const string SHOW_RECOMMENDATIONS_BLOCK = 'show_annif_block';

  /**
   * {@inheritDoc}
   */
  public function isRecommendableContent(): bool {
    return !$this->get(self::KEYWORDFIELD)->isEmpty();
  }

  /**
   * {@inheritDoc}
   */
  public function showRecommendationsBlock(): bool {
    return $this->hasKeywords() &&
      $this->get(self::SHOW_RECOMMENDATIONS_BLOCK)->value;
  }

  /**
   * {@inheritDoc}
   */
  public function hasKeywords(): bool {
    return !$this->get(self::KEYWORDFIELD)->isEmpty();
  }

  /**
   * {@inheritDoc}
   */
  public function getKeywordFieldName(): string {
    return self::KEYWORDFIELD;
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheTagsToInvalidate(): array {
    $parentCacheTags = parent::getCacheTagsToInvalidate();
    if (!$this->hasField(self::getKeywordFieldName())) {
      return $parentCacheTags;
    }

    $keywordsCacheTags = $this->getKeywordsCacheTags();
    return Cache::mergeTags($parentCacheTags, $keywordsCacheTags);
  }

  /**
   * Get the cache tags for all of the keywords.
   *
   * @return array
   *   Array of cache tags for keywrods.
   */
  protected function getKeywordsCacheTags(): array {
    $terms = $this->get(self::getKeywordFieldName())->referencedEntities();

    $tags = array_map(
      fn ($term) => $term->getCacheTags(),
      $terms
    );
    return array_merge(...$tags);
  }

}
