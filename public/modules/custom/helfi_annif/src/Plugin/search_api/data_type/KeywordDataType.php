<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Plugin\search_api\data_type;

use Drupal\helfi_annif\Keyword\Keyword;
use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a keyword data type.
 *
 * @SearchApiDataType(
 *    id = "ai_keyword",
 *    label = @Translation("Helfi Keyword"),
 *    description = @Translation("AI generated keywords."),
 *  )
 * /
 */
class KeywordDataType extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
    return array_map(static fn (Keyword $keyword) => [
      "label" => $keyword->label,
      "score" => $keyword->score,
      "uri" => $keyword->uri,
    ], $value);
  }

}
