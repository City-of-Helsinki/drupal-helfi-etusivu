<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Search;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\helfi_etusivu\Entity\Search\Suggestion;

/**
 * Loads search suggestions in the order defined by the drag UI.
 *
 * @see \Drupal\draggableviews\Plugin\views\sort\DraggableViewsSort::query()
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

  public function __construct(
    private readonly Connection $connection,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Gets ordered suggestions.
   *
   * @return \Drupal\helfi_etusivu\Entity\Search\Suggestion[]
   *   Suggestion entities.
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

    $query->leftJoin('draggableviews_structure', 'dvs',
      "[dvs].[entity_id] = [base].[$idKey]
        AND [dvs].[view_name] = :view_name
        AND [dvs].[view_display] = :view_display
        AND [dvs].[langcode] = :langcode",
      [
        ':view_name' => self::VIEW_ID,
        ':view_display' => self::VIEW_DISPLAY_ID,
        ':langcode' => $langcode,
      ],
    );

    // For rows that don't have ordering set. COALESCE evaluates the
    // arguments in order and returns the first value that is not
    // NULL. See 'draggable_views_null_order: after' config.
    $query->addExpression('COALESCE([dvs].[weight], :null_order)', 'dv_weight', [
      ':null_order' => PHP_INT_MAX,
    ]);

    $query->orderBy('dv_weight');
    $query->orderBy('base.' . $idKey);
    $query->range(0, 100);

    $ids = $query->execute()?->fetchCol() ?: [];

    if (!$ids) {
      return [];
    }

    $entities = $this->entityTypeManager
      ->getStorage('helfi_search_suggestion')
      ->loadMultiple($ids);

    $result = [];
    foreach ($ids as $id) {
      $entity = $entities[$id] ?? NULL;

      if ($entity instanceof Suggestion && $entity->hasTranslation($langcode)) {
        $result[] = $entity->getTranslation($langcode);
      }
    }

    return $result;
  }

}
