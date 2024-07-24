<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Interface for recommendable entity.
 */
interface RecommendableInterface extends EntityInterface, FieldableEntityInterface {

  /**
   * Current entity can be used as a recommendation based on keywords.
   *
   * @return bool
   *   Entity can be used as recommendation
   */
  public function isRecommendableContent(): bool;

  /**
   * Check if block has been manually disabled for single content page.
   *
   * @return bool
   *   Block is visible.
   */
  public function isBlockSetVisible(): bool;

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
   * Get keyword field name.
   *
   * @return string
   *   Name of the field which holds the keywords.
   */
  public function getKeywordFieldName(): string;

}
