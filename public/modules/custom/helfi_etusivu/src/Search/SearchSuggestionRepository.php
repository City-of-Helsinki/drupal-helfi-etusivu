<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Search;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\helfi_etusivu\Entity\Search\Suggestion;

/**
 * Loads search suggestions in the order defined by the drag UI.
 *
 * This repository re-implements the ordering of
 * \Drupal\draggableviews\Plugin\views\sort\DraggableViewsSort::query() rather
 * than executing the view, so the API does not have to bootstrap Views on
 * every request. The COALESCE below must stay in sync with the view's
 * 'draggable_views_null_order' setting.
 */
final class SearchSuggestionRepository {

  /**
   * The view that owns the stored order.
   *
   * @see helfi_etusivu_views_query_alter()
   */
  public const string VIEW_ID = 'search_suggestions';

  /**
   * The display of self::VIEW_ID that owns the stored order.
   *
   * @see draggableviews_views_submit()
   */
  public const string VIEW_DISPLAY_ID = 'search_suggestions';

  /**
   * Hard upper bound for one API payload.
   */
  public const int MAX_ITEMS = 50;

  public function __construct(
    private readonly Connection $connection,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Gets the suggestions for a language, in the editor's order.
   *
   * @param string $langcode
   *   The content language to load suggestions for.
   *
   * @return \Drupal\helfi_etusivu\Entity\Search\Suggestion[]
   *   Suggestion entities, translated to $langcode, ordered.
   */
  public function getOrdered(string $langcode): array {
    $definition = $this->entityTypeManager->getDefinition('helfi_search_suggestion');

    $idKey = $definition->getKey('id');
    $langcodeKey = $definition->getKey('langcode');
    $dataTable = $definition->getDataTable();
    assert(is_string($idKey) && is_string($langcodeKey) && is_string($dataTable));

    $query = $this->connection->select($dataTable, 'base');
    $query->addField('base', $idKey, 'entity_id');
    $query->condition('base.' . $langcodeKey, $langcode);

    // Mirrors the join built by DraggableViewsSort::query(), plus the langcode
    // condition that helfi_etusivu_views_query_alter() adds for this view.
    // 'args' is deliberately left out: the view takes no arguments, so the
    // stored value is always '[]' and the view's own join skips it too.
    // A LEFT JOIN also keeps stale rows harmless - draggableviews never
    // deletes them when an entity is deleted.
    $query->leftJoin('draggableviews_structure', 'dvs',
      '[dvs].[entity_id] = [base].[' . $idKey . ']
        AND [dvs].[view_name] = :view_name
        AND [dvs].[view_display] = :view_display
        AND [dvs].[langcode] = :langcode',
      [
        ':view_name' => self::VIEW_ID,
        ':view_display' => self::VIEW_DISPLAY_ID,
        ':langcode' => $langcode,
      ],
    );

    // Suggestions created since the last "Save order" have no weight row and
    // sort last, matching 'draggable_views_null_order: after' in the view.
    $query->addExpression('COALESCE([dvs].[weight], :null_order)', 'dv_weight', [
      ':null_order' => PHP_INT_MAX,
    ]);
    $query->orderBy('dv_weight');
    // Stable tiebreaker for rows that share a weight or have none.
    $query->orderBy('base.' . $idKey);
    $query->range(0, self::MAX_ITEMS);

    $ids = $query->execute()?->fetchCol() ?: [];

    if (!$ids) {
      return [];
    }

    $entities = $this->entityTypeManager
      ->getStorage('helfi_search_suggestion')
      ->loadMultiple($ids);

    $result = [];
    // loadMultiple() returns entities keyed by id, not in the queried order.
    foreach ($ids as $id) {
      $entity = $entities[$id] ?? NULL;

      if ($entity instanceof Suggestion && $entity->hasTranslation($langcode)) {
        $result[] = $entity->getTranslation($langcode);
      }
    }

    return $result;
  }

}
