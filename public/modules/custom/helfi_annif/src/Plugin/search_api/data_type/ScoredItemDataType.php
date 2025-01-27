<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a string data type.
 *
 * @SearchApiDataType(
 *   id = "scored_item",
 *   label = @Translation("Scored item"),
 *   description = @Translation("Item with score."),
 *   default = "true"
 * )
 */
class ScoredItemDataType extends DataTypePluginBase {
}
