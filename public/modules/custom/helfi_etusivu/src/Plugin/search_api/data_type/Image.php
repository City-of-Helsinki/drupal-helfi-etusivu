<?php

namespace Drupal\helfi_etusivu\Plugin\search_api\data_type;

use Drupal\image\Entity\ImageStyle;
use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Get file url from media entity.
 *
 * @SearchApiDataType(
 *   id = "etusivu_image",
 *   label = @Translation("Image"),
 *   description = @Translation("Image"),
 *   default = "true",
 *   fallback_type = "string"
 * )
 */
class Image extends DataTypePluginBase {

  /**
   * {@inheritDoc}
   */
  public function getValue($value) {
    if (!$value->hasField('field_media_image') || !$file = $value->get('field_media_image')->entity) {
      return '';
    }

    $imageStyle = ImageStyle::load('3_2_l');
    $imagePath = $file->getFileUri();

    return $imageStyle->buildUrl($imagePath);
  }

}
