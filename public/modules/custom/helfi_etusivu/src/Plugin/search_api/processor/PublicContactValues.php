<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_etusivu\Entity\Search\ContactDetails;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Indexes phone numbers and addresses of contact details.
 */
#[SearchApiProcessor(
  id: 'helfi_public_contact_values',
  label: new TranslatableMarkup('Contact values'),
  description: new TranslatableMarkup('Indexes phone numbers and addresses of contact details.'),
  stages: [
    'add_properties' => 0,
  ],
)]
final class PublicContactValues extends ProcessorPluginBase {

  /**
   * Maps the added properties to the source fields.
   */
  private const array PROPERTIES = [
    'public_phones' => 'phones',
    'public_addresses' => 'addresses',
  ];

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index): bool {
    return array_any($index->getDatasources(), fn($datasource) => $datasource->getEntityTypeId() === 'helfi_search_contact');
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL): array {
    if ($datasource?->getEntityTypeId() !== 'helfi_search_contact') {
      return [];
    }

    return [
      'public_phones' => new ProcessorProperty([
        'label' => $this->t('Public phone numbers'),
        'description' => $this->t('Phone numbers.'),
        'type' => 'object',
        'processor_id' => $this->getPluginId(),
      ]),
      'public_addresses' => new ProcessorProperty([
        'label' => $this->t('Public addresses'),
        'description' => $this->t('Addresses.'),
        'type' => 'object',
        'processor_id' => $this->getPluginId(),
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param \Drupal\search_api\Item\ItemInterface<mixed> $item
   */
  public function addFieldValues(ItemInterface $item): void {
    $entity = $item->getOriginalObject()?->getValue();

    if (!$entity instanceof ContactDetails) {
      return;
    }

    foreach (self::PROPERTIES as $property => $field_name) {
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), $item->getDatasourceId(), $property);

      if (!$fields) {
        continue;
      }

      foreach ($fields as $field) {
        foreach ($entity->get($field_name)->getValue() as $value) {
          $field->addValue([
            'value' => $value['value'],
            'type' => $value['type'] ?? NULL,
            'info' => $value['info'] ?? NULL,
          ]);
        }
      }
    }
  }

}
