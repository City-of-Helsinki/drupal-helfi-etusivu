<?php

namespace Drupal\helfi_etusivu\Plugin\search_api\processor;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Indexes uuid with langcode.
 *
 * @SearchApiProcessor(
 *   id = "uuid_langcode",
 *   label = @Translation("UUID Langcode"),
 *   description = @Translation("Indexes uuid and langcode for faster lookup"),
 *   stages = {
 *     "add_properties" = 0
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
final class UuidLangcode extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DataSourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('UUID Langcode'),
        'description' => $this->t('Indexes uuid with langcode'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];

      $properties['uuid_langcode'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(Iteminterface $item) {
    if (!$entity = $item->getOriginalObject()->getValue()) {
      return;
    }
    if (!$entity instanceof ContentEntityInterface) {
      return;
    }
    $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'uuid_langcode');
    foreach ($fields as $field) {
      $field->addValue(sprintf('%s:%s', $entity->uuid(), $entity->language()->getId()));
    }
  }

}
