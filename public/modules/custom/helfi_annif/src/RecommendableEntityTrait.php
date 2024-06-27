<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

/**
 * Annif-recommendation trait.
 */
trait RecommendableEntityTrait {

  static string $keywordField = 'field_annif_keywords';

  static string $showInRecommendations = 'field_show_in_recommendations';

  static string $showRecommendationsBlock = 'field_show_recommendations_block';

  /**
   * {@inheritDoc}
   */
  public function isRecommendableEntity(): bool {
    return $this->hasField(self::$keywordField);
  }

  /**
   * {@inheritDoc}
   */
  public function isRecommendableContent(): bool {
    // Not having the field to hide this entity from recommendations
    // should not hide it by default.
    if (
      !$this->hasField(self::$showInRecommendations)
    ) {
      return TRUE;
    }

    return $this->isRecommendableEntity() &&
      !$this->get(self::$keywordField)->isEmpty() &&
      $this->get(self::$showInRecommendations)->value;
  }

  /**
   * {@inheritDoc}
   */
  public function showRecommendationsBlock(): bool {
    // Not having the field to hide the block should not hide it by default.
    if (!$this->hasField(self::$showRecommendationsBlock)) {
      return TRUE;
    }

    return $this->isRecommendableEntity() &&
      $this->hasKeywords() &&
      $this->get(self::$showRecommendationsBlock)->value;
  }

  /**
   * {@inheritDoc}
   */
  public function hasKeywords(): bool {
    return !$this->get(self::$keywordField)->isEmpty();
  }

  /**
   * {@inheritDoc}
   */
  public static function getKeywordFieldName(): string {
    return self::$keywordField;
  }

}
