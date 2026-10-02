<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * A contact value from numerot.hel.fi.
 *
 * @see \Drupal\helfi_etusivu\Entity\Search\ContactDetails
 *
 * @property string $value
 * @property string|null $type
 * @property string|null $info
 */
#[FieldType(
  id: 'helfi_search_contact_value',
  label: new TranslatableMarkup('Contact value', options: ['context' => 'Helfi search']),
  description: new TranslatableMarkup('A contact value with a type and optional additional information.', options: ['context' => 'Helfi search']),
  no_ui: TRUE,
)]
final class ContactValueItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Value'))
      ->setRequired(TRUE);
    $properties['type'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Type'));
    $properties['info'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Additional information'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, array<string, array<string, int|string>>>
   *   The schema.
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'normal',
        ],
        'type' => [
          'type' => 'varchar',
          'length' => 64,
        ],
        'info' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
