<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a keyword data type.
 *
 * @SearchApiDataType(
 *    id = "noindex",
 *    label = @Translation("Noindex"),
 *    description = @Translation("String is not indexed."),
 *    fallback_type = "string",
 * )
 */
class Noindex extends DataTypePluginBase {
}
