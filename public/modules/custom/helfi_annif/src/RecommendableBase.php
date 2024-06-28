<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;

/**
 * Base class for recommendations.
 */
abstract class RecommendableBase extends Node implements EntityInterface, RecommendableInterface {

  public const string KEYWORDFIELD = 'field_annif_keywords';

  public const string SHOWINRECOMMENDATION = 'field_show_in_recommendations';

  public const string SHOWRECOMMENDATIONSBLOCK = 'field_show_recommendations_block';

  /**
   * {@inheritDoc}
   */
  public function isRecommendableEntity(): bool {
    return $this->hasField(self::KEYWORDFIELD);
  }

  /**
   * {@inheritDoc}
   */
  public function isRecommendableContent(): bool {
    // Not having the field to hide this entity from recommendations
    // should not hide it by default.
    if (
      !$this->hasField(self::SHOWINRECOMMENDATION)
    ) {
      return TRUE;
    }

    return $this->isRecommendableEntity() &&
      !$this->get(self::KEYWORDFIELD)->isEmpty() &&
      $this->get(self::SHOWINRECOMMENDATION)->value;
  }

  /**
   * {@inheritDoc}
   */
  public function showRecommendationsBlock(): bool {
    // Not having the field to hide the block should not hide it by default.
    if (!$this->hasField(self::SHOWRECOMMENDATIONSBLOCK)) {
      return TRUE;
    }

    return $this->isRecommendableEntity() &&
      $this->hasKeywords() &&
      $this->get(self::SHOWRECOMMENDATIONSBLOCK)->value;
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
  public static function getKeywordFieldName(): string {
    return self::KEYWORDFIELD;
  }

}
