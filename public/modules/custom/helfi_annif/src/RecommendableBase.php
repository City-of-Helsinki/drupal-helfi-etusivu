<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;

/**
 * Base class for recommendations.
 */
abstract class RecommendableBase extends Node implements EntityInterface, RecommendableInterface {

  protected const string KEYWORDFIELD = 'field_annif_keywords';

  protected const string SHOWINRECOMMENDATION = 'field_show_in_recommendations';

  protected const string SHOWRECOMMENDATIONSBLOCK = 'field_show_recommendations_block';

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

    return !$this->get(self::KEYWORDFIELD)->isEmpty() &&
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

    return $this->hasKeywords() &&
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
  public function getKeywordFieldName(): string {
    return self::KEYWORDFIELD;
  }

}
