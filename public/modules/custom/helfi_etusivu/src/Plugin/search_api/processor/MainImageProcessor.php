<?php

declare(strict_types=1);

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
  public function getPropertyDefinitions(?DataSourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Main image'),
        'description' => $this->t('Indexes main image uri in correct image style'),
        'type' => 'object',
        'processor_id' => $this->getPluginId(),
      ];

      $properties['main_image_url'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $dataSourceId = $item->getDataSourceId();

    if ($dataSourceId !== 'entity:node' || !$node = $item->getOriginalObject()->getValue()) {
      return;
    }

    $type = $node->getType();

    if ($type !== 'news_item' && $type !== 'news_article') {
      return;
    }

    $image = $node->get('field_main_image')->entity;

    if (!$image || !$image->hasField('field_media_image') || !$file = $image->get('field_media_image')->entity) {
      return;
    }

    $imagePath = $file->getFileUri();
    $imageStyles = [
      '1.5_378w_252h' => '1248',
      '1.5_341w_227h' => '992',
      '1.5_264w_176h' => '768',
      '1.5_217w_145h' => '576',
      '1.5_511w_341h' => '320',
      '1.5_756w_504h_LQ' => '1248_2x',
      '1.5_682w_454h_LQ' => '992_2x',
      '1.5_528w_352h_LQ' => '768_2x',
      '1.5_434w_290h_LQ' => '576_2x',
      '1.5_1022w_682h_LQ' => '320_2x',
    ];

    $urls = [];
    foreach ($imageStyles as $styleName => $breakpoint) {
      $imageStyle = ImageStyle::load($styleName);
      if ($imageStyle) {
        $urls[$breakpoint] = $imageStyle->buildUrl($imagePath);
      }
    }

    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'main_image_url');
    foreach ($fields as $field) {
      $field->addValue(json_encode($urls));
    }
  }

}
