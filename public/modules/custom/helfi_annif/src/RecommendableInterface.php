<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Interface for recommendable entity.
 */
interface RecommendableInterface extends FieldableEntityInterface {

  /**
   * Entity can handle recommendations.
   *
   * @return bool
   *   Entity can handle recommendations.
   */
  public function isRecommendableEntity(): bool;

  /**
   * Current entity can be used as a recommendation based on keywords.
   *
   * @return bool
   *   Entity can be used as recommendation
   */
  public function isRecommendableContent(): bool;

  /**
   * Current entity should show recommendations block.
   *
   * @return bool
   *   Show recommendations block.
   */
  public function showRecommendationsBlock(): bool;

  /**
   * Check if the entity has keywords.
   *
   * @return bool
   *   Entity has keywords.
   */
  public function hasKeywords(): bool;

  /**
   * Get cache tags for keywords.
   *
   * @return array
   *   Keywords cache tags.
   */
  public function getKeywordsCacheTags(): array;

  /**
   * Invalidate the cache tags of the keyword taxonomy terms.
   */
  public function invalidateKeywordsCacheTags(): void;

  /**
   * Get keyword field name.
   *
   * @return string
   *   Name of the field which holds the keywords.
   */
  public function getKeywordFieldName(): string;

}
