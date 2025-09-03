<?php

declare(strict_types=1);

namespace Drupal\Tests\search_api\Kernel\Processor;

use Drupal\helfi_etusivu\Plugin\search_api\processor\UuidLangcode;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\search_api\Item\Field;
use Drupal\Tests\search_api\Kernel\PostRequestIndexingTrait;

/**
 * Tests the entity uuid+langcode property.
 *
 * @group helfi_etusivu
 */
class UuidLangcodeTest extends ProcessorTestBase {

  use PostRequestIndexingTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'serialization',
    'helfi_etusivu',
    'helfi_api_base',
    'big_pipe',
    'helfi_react_search',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp($processor = NULL): void {
    parent::setUp('entity_type');

    NodeType::create([
      'type' => 'test_node_bundle',
    ])->save();

    $entity_type_field = new Field($this->index, 'uuid_langcode');
    $entity_type_field->setType('string');
    $entity_type_field->setPropertyPath('uuid_langcode');
    $entity_type_field->setLabel('UUID Langcode');
    $this->index->addField($entity_type_field);
    $this->index->setOption('index_directly', TRUE);
    $this->index->save();
  }

  /**
   * Tests that field values are added correctly.
   */
  public function testAddFieldValues() : void {
    $node = Node::create([
      'title' => 'Test',
      'type' => 'test_node_bundle',
    ]);
    $node->save();

    $this->triggerPostRequestIndexing();
    $query = $this->index->query();
    // We don't need a query condition as we have only one node anyway.
    $results = $query->execute();
    $values = [];
    /** @var \Drupal\search_api\Item\ItemInterface $result */
    foreach ($results as $result) {
      $field_values = $result->getField('uuid_langcode')->getValues();
      $values[] = $field_values;
    }

    $this->assertCount(1, $values);
    $this->assertEquals([UuidLangcode::getUuidLangcode($node)], $values[0]);
  }

}
