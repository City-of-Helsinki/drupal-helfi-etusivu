<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Search\Numerot;

use Drupal\helfi_etusivu\Entity\Search\ContactDetails;
use Drupal\search_api\Item\Field;
use Drupal\Tests\search_api\Kernel\Processor\ProcessorTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the public contact values processor.
 */
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
final class PublicContactValuesTest extends ProcessorTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'diff',
    'helfi_api_base',
    'helfi_etusivu',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp($processor = NULL): void {
    parent::setUp('helfi_public_contact_values');

    $this->installEntitySchema('helfi_search_contact');

    $this->index->addDatasource(\Drupal::service('search_api.plugin_helper')
      ->createDatasourcePlugin($this->index, 'entity:helfi_search_contact'));

    foreach (['public_phones', 'public_addresses'] as $property) {
      $field = new Field($this->index, $property);
      $field->setType('object');
      $field->setDatasourceId('entity:helfi_search_contact');
      $field->setPropertyPath($property);
      $this->index->addField($field);
    }
  }

  /**
   * Tests that the values are indexed as objects.
   */
  public function testAddFieldValues(): void {
    $contact = ContactDetails::create([
      'id' => 'test-contact',
      'name_parts' => ['Matti', 'Meikäläinen'],
      'phones' => [
        ['value' => '+358 9 123 4567', 'type' => 'landline', 'info' => NULL],
        ['value' => '+358 9 310 1111', 'type' => 'customer_service', 'info' => 'Vaihde'],
      ],
      'addresses' => [
        ['value' => "Testikatu 1\n00100 Helsinki", 'type' => 'street', 'info' => NULL],
      ],
    ]);
    $contact->save();

    $item = $this->generateItem([
      'datasource' => 'entity:helfi_search_contact',
      'item' => $contact->getTypedData(),
      'item_id' => 'test-contact:en',
    ]);
    $this->processor->addFieldValues($item);

    $this->assertEquals([
      ['value' => '+358 9 123 4567', 'type' => 'landline', 'info' => NULL],
      ['value' => '+358 9 310 1111', 'type' => 'customer_service', 'info' => 'Vaihde'],
    ], $item->getField('public_phones')->getValues());
    $this->assertEquals([
      ['value' => "Testikatu 1\n00100 Helsinki", 'type' => 'street', 'info' => NULL],
    ], $item->getField('public_addresses')->getValues());
  }

}
