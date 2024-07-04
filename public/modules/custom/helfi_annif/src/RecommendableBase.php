<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;

/**
 * Base class for recommendations.
 */
abstract class RecommendableBase extends Node implements EntityInterface, RecommendableInterface {

  protected const string KEYWORDFIELD = 'the_annif_keywords';

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

}
