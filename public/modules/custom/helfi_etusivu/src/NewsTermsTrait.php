<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu;

use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;

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

  /**
   * Gets taxonomy term names with a link to the filtered news archive page.
   *
   * @return array
   *   An array of taxonomy terms with links, keyed by field name.
   */
  public function getTaxonomyTermsWithArchiveLinks(): array {

    // Map the term fields on node to term groups.
    $fields = [
      'field_news_item_tags' => 'topic',
      'field_news_neighbourhoods' => 'neighbourhoods',
      'field_news_groups' => 'groups',
    ];

    $result = [];

    // Go through each field.
    foreach ($fields as $field_name => $url_param_name) {
      if (!$this->hasField($field_name) || $this->get($field_name)->isEmpty()) {
        continue;
      }

      $field = $this->get($field_name);

      // Make sure that the field is term reference field.
      if ($field instanceof EntityReferenceFieldItemListInterface) {
        $terms = $field->referencedEntities();

        // Get node's language code.
        $language = $this->language()->getId();

        // Define language-specific base paths for news archive.
        $base_paths = [
          'fi' => '/fi/uutiset/etsi-uutisia',
          'sv' => '/sv/nyheter/sok-efter-nyheter',
          'en' => '/en/news/search-for-news',
        ];

        // Default to English if language is not found.
        $base_path = $base_paths[$language] ?? $base_paths['en'];

        foreach ($terms as $term) {

          // Get the term label in the node's language.
          $term_name = ($term instanceof TranslatableInterface && $term->hasTranslation($language))
            ? $term->getTranslation($language)->label()
            : $term->label();

          $result[$field_name][] = [
            'name' => $term_name,
            'url' => $base_path . '?' . $url_param_name . '[0]=' . $term->id(),
          ];
        }
      }
    }

    return $result;
  }

}
