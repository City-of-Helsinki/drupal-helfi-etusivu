<?php
declare(strict_types=1);

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
  public function getPropertyDefinitions(DataSourceInterface $datasource = NULL) : array {
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
   * Gets the UUID langcode for given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The uuid+langcode.
   */
  public static function getUuidLangcode(ContentEntityInterface $entity) : string {
    return sprintf('%s:%s', $entity->uuid(), $entity->language()->getId());
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(Iteminterface $item) : void {
    $entity = $item->getOriginalObject()?->getValue();

    if (!$entity instanceof ContentEntityInterface) {
      return;
    }
    $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'uuid_langcode');
    foreach ($fields as $field) {
      $field->addValue(self::getUuidLangcode($entity));
    }
  }

}
