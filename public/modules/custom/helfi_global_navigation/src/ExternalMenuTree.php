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
   * Build a renderable array from external menu items.
   *
   * @return array
   *   The value to use for the #items property of a renderable menu.
   */
  public function build() {
    return $this->transformItems($this->tree);
  }

  /**
   * Getter function for tree instance variable.
   *
   * @return array
   *   The tree.
   */
  public function getTree() {
    return $this->tree;
  }

  /**
   * Build a renderable menu item array for deriver.
   *
   * @param array $tree
   *   Tree as array.
   *
   * @return array
   *   The renderable array.
   */
  protected function buildItems(array $tree) {
    $items = [];

    foreach ($tree as $item) {
      $items[] = [
        'title' => $item->name,
        'url' => $item->menu_tree->url,
      ];
    }

    return $items;
  }

}
