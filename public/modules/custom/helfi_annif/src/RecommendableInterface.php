<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

/**
 * Interface for recommendable entity.
 */
interface RecommendableInterface {

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
}
