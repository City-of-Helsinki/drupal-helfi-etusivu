<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a suggestted topics entity type.
 */
interface SuggestedTopicsInterface extends ContentEntityInterface {

  /**
   * Check if the entity has keywords.
   *
   * @return bool
   *   Entity has keywords.
   */
  public function hasKeywords(): bool;

}
