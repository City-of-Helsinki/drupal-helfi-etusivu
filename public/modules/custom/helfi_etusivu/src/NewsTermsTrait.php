<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu;

/**
 * Retrieve news terms from entity.
 */
trait NewsTermsTrait {

  /**
   * Retrieve news terms from entity.
   *
   * @return string
   *   Returns imploded list of term ids.
   */
  public function getNewsTerms(): string {
    $list = '';

    // If node has "field_news_item_tags" field, set its ids to Matomo.
    if (
      $this->hasField('field_news_item_tags') &&
      $this->isPublished() &&
      !$this->get('field_news_item_tags')->isEmpty()
    ) {
      $tags = $this->get('field_news_item_tags')->getValue();
      $tag_list = [];

      foreach ($tags as $tag) {
        $tag_list[] = $tag['target_id'];
      }

      $list = implode(',', $tag_list);
    }

    return $list;
  }

}
