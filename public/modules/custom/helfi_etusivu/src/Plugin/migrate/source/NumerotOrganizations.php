<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;

/**
 * Source plugin for the organization tree from the numerot.hel.fi JSON:API.
 *
 * The organizations are fetched once per language. The API returns the
 * Finnish version for missing translations.
 *
 * @phpstan-type OrganizationRow array{
 *   id: string,
 *   language: string,
 *   name: string|null,
 *   weight: int,
 *   parent_id: string|null
 * }
 */
#[MigrateSource(id: 'helfi_numerot_organizations')]
final class NumerotOrganizations extends NumerotSourceBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrls(): array {
    $urls = [];

    foreach (self::LANGUAGES as $language) {
      $urls[$language] = self::BASE_URL . "/$language/jsonapi/taxonomy_term/ou?filter[status]=1&sort=drupal_internal__tid&page[limit]=50&fields[taxonomy_term--ou]=name,weight,parent,langcode";
    }

    return $urls;
  }

  /**
   * {@inheritdoc}
   *
   * @return list<OrganizationRow>
   *   The source rows.
   */
  protected function parseResponse(\stdClass $content, string $language): array {
    $rows = [];

    foreach ((array) $content->data as $term) {
      $attributes = $term->attributes ?? new \stdClass();

      $rows[] = [
        'id' => $term->id,
        'language' => $language,
        'name' => $this->text($attributes->name ?? NULL),
        'weight' => (int) ($attributes->weight ?? 0),
        'parent_id' => $this->getParentId($term),
      ];
    }

    return $rows;
  }

  /**
   * Gets the parent ID of a term.
   *
   * @param \stdClass $term
   *   The term resource.
   *
   * @return string|null
   *   The parent API ID, or NULL for root terms.
   */
  private function getParentId(\stdClass $term): ?string {
    $parents = $term->relationships->parent->data ?? [];

    // Only the first parent is used. Root terms have a 'virtual' parent.
    $id = is_array($parents) ? $this->text($parents[0]->id ?? NULL) : NULL;

    return $id === 'virtual' ? NULL : $id;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<key-of<OrganizationRow>, string>
   */
  public function fields(): array {
    return [
      'id' => 'The numerot.hel.fi API ID',
      'language' => 'The language code',
      'name' => 'Name in the row language',
      'weight' => 'Weight',
      'parent_id' => 'Parent API ID',
    ];
  }

}
