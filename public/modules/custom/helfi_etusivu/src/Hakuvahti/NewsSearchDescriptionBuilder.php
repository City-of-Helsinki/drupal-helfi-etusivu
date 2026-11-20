<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Hakuvahti;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Builds a plain-text news search description from an Elasticsearch query.
 *
 * Filter labels come from published terms of the expected vocabularies, looked
 * up by the term ids in the query. The keyword is truncated.
 */
class NewsSearchDescriptionBuilder {

  public const int MAX_LENGTH = 999;
  public const int MAX_KEYWORD_LENGTH = 10;
  public const int MAX_TERMS_PER_FIELD = 50;

  /**
   * Index field => vocabulary, in the order the search monitor lists them.
   */
  private const array TERM_FIELDS = [
    'news_tags' => 'news_tags',
    'neighbourhoods' => 'news_neighbourhoods',
    'news_groups' => 'news_group',
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityRepositoryInterface $entityRepository,
  ) {
  }

  /**
   * Builds the search description.
   *
   * @param array<array-key, mixed> $query
   *   The decoded Elasticsearch query.
   * @param string $langcode
   *   The language of the labels.
   *
   * @return string
   *   The description, or an empty string if the query has no known filters.
   */
  public function build(array $query, string $langcode): string {
    $must = $this->getMustClauses($query);
    $parts = [];

    $keyword = $this->getKeyword($must);
    if ($keyword !== '') {
      $parts[] = Unicode::truncate($keyword, self::MAX_KEYWORD_LENGTH, add_ellipsis: TRUE);
    }

    $idsByField = [];
    foreach (array_keys(self::TERM_FIELDS) as $field) {
      $idsByField[$field] = $this->getTermIds($must, $field);
    }

    $ids = array_values(array_unique(array_merge(...array_values($idsByField))));
    $terms = $ids ? $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($ids) : [];

    foreach (self::TERM_FIELDS as $field => $vid) {
      foreach ($idsByField[$field] as $id) {
        $term = $terms[$id] ?? NULL;
        if (!$term instanceof TermInterface || $term->bundle() !== $vid) {
          continue;
        }
        $term = $this->entityRepository->getTranslationFromContext($term, $langcode);
        if ($term instanceof TermInterface && $term->isPublished()) {
          $parts[] = (string) $term->label();
        }
      }
    }

    return $this->join($parts);
  }

  /**
   * Gets the query.bool.must clauses.
   *
   * @param array<array-key, mixed> $query
   *   The decoded Elasticsearch query.
   *
   * @return list<mixed>
   *   The clauses.
   */
  private function getMustClauses(array $query): array {
    $bool = is_array($query['query'] ?? NULL) ? ($query['query']['bool'] ?? NULL) : NULL;
    $must = is_array($bool) ? ($bool['must'] ?? NULL) : NULL;
    return is_array($must) ? array_values($must) : [];
  }

  /**
   * Gets the keyword of the first keyword clause.
   *
   * The wildcard clause keeps the keyword's original case, the
   * simple_query_string clause has it lowercased with a trailing '~'. Control
   * and format characters are replaced with spaces.
   *
   * @param list<mixed> $must
   *   The must clauses.
   *
   * @return string
   *   The keyword, or an empty string.
   */
  private function getKeyword(array $must): string {
    foreach ($must as $clause) {
      $should = is_array($clause) && is_array($clause['bool'] ?? NULL) ? ($clause['bool']['should'] ?? NULL) : NULL;
      if (!is_array($should)) {
        continue;
      }

      $wildcard = NULL;
      $simpleQuery = NULL;
      foreach ($should as $item) {
        if (!is_array($item)) {
          continue;
        }
        if (is_array($item['wildcard'] ?? NULL) && is_string($item['wildcard']['title.keyword'] ?? NULL)) {
          $wildcard ??= preg_replace('/^\*|\*$/', '', $item['wildcard']['title.keyword']);
        }
        if (is_array($item['simple_query_string'] ?? NULL) && is_string($item['simple_query_string']['query'] ?? NULL)) {
          $simpleQuery ??= preg_replace('/~$/', '', $item['simple_query_string']['query']);
        }
      }

      $keyword = trim((string) preg_replace('/[\p{Cc}\p{Cf}]+/u', ' ', (string) ($wildcard ?? $simpleQuery)));
      if ($keyword !== '') {
        return $keyword;
      }
    }
    return '';
  }

  /**
   * Gets the term ids of the given index field.
   *
   * @param list<mixed> $must
   *   The must clauses.
   * @param string $field
   *   The index field.
   *
   * @return list<int>
   *   Unique positive ids in request order.
   */
  private function getTermIds(array $must, string $field): array {
    $ids = [];
    foreach ($must as $clause) {
      $values = is_array($clause) && is_array($clause['terms'] ?? NULL) ? ($clause['terms'][$field] ?? NULL) : NULL;
      if (!is_array($values)) {
        continue;
      }
      foreach ($values as $value) {
        if (is_int($value) && $value > 0 && !in_array($value, $ids, TRUE)) {
          $ids[] = $value;
          if (count($ids) >= self::MAX_TERMS_PER_FIELD) {
            return $ids;
          }
        }
      }
    }
    return $ids;
  }

  /**
   * Joins whole parts up to the maximum length.
   *
   * @param list<string> $parts
   *   The parts.
   *
   * @return string
   *   The joined parts.
   */
  private function join(array $parts): string {
    $description = '';
    foreach ($parts as $part) {
      $candidate = $description === '' ? $part : $description . ', ' . $part;
      if (mb_strlen($candidate) > self::MAX_LENGTH) {
        break;
      }
      $description = $candidate;
    }
    return $description;
  }

}
