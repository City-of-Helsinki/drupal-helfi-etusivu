<?php

namespace Drupal\helfi_etusivu\Plugin\search_api\processor;

use Drupal\image\Entity\ImageStyle;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Indexes main image uri in correct image style.
 *
 * @SearchApiProcessor(
 *   id = "main_image_url",
 *   label = @Translation("Main image"),
 *   description = @Translation("Indexes main image uri in correct image style"),
 *   stages = {
 *     "add_properties" = 0
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class MainImageProcessor extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DataSourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Main image'),
        'description' => $this->t('Indexes main image uri in correct image style'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];

      $properties['main_image_url'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(Iteminterface $item) {
    $dataSourceId = $item->getDataSourceId();

    if ($dataSourceId !== 'entity:node' || !$node = $item->getOriginalObject()->getValue()) {
      return;
    }

    $type = $node->getType();
    if ($type !== 'news_item') {
      return;
    }

    $image = $node->get('field_main_image')->entity;

    if (!$image || !$image->hasField('field_media_image') || !$file = $image->get('field_media_image')->entity) {
      return;
    }

    $imageStyle = ImageStyle::load('3_2_l');
    $imagePath = $file->getFileUri();
    $value = $imageStyle->buildUrl($imagePath);

    $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'main_image_url');
    foreach ($fields as $field) {
      $field->addValue($value);
    }
  }

}
