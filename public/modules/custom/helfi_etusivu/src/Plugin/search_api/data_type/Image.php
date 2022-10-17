<?php

namespace Drupal\helfi_etusivu\Plugin\search_api\data_type;

use Drupal\image\Entity\ImageStyle;
use Drupal\search_api\Plugin\search_api\data_type\StringDataType;

/**
 * Get file url from media entity.
 *
 * @SearchApiDataType(
 *   id = "etusivu_image",
 *   label = @Translation("Image"),
 *   description = @Translation("Image"),
 *   default = "true"
 * )
 */
class Image extends StringDataType {

  /**
   * {@inheritDoc}
   */
  public function getValue($value) {
    if (!$value->hasField('field_media_image')) {
      return '';
    }

    if ($file = $value->get('field_media_image')->entity) {
      $imageStyle = ImageStyle::load('3_2_l');
      $imagePath = $file->getFileUri();
      $imageUri = $imageStyle->buildUri($imagePath);

      if (!file_exists($imageUri)) {
        $imageStyle->createDerivative($imagePath, $imageUri);
      }

      return helfi_proxy_absolute_url($imageStyle->buildUrl($imagePath));
    }
  }

}
