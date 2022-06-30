<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation;

/**
 * Class for building menu tree from external source.
 */
class ExternalMenuTree {

  /**
   * Constructs an instance of ExternalMenuTree.
   */
  public function __construct(protected array $tree) {}

  /**
   * Getter function for tree instance variable.
   *
   * @return array
   *   The tree.
   */
  public function getTree() {
    return $this->tree;
  }

}
