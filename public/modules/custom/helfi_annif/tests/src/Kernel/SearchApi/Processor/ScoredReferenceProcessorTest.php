<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_annif\Kernel\Processor;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\search_api\Item\Field;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\search_api\Kernel\PostRequestIndexingTrait;
use Drupal\Tests\search_api\Kernel\Processor\ProcessorTestBase;

/**
 * Tests the scored reference processor.
 *
 * @group helfi_annif
 */
class ScoredReferenceProcessorTest extends ProcessorTestBase {

  use PostRequestIndexingTrait;

  /**
   * Test vocabulary.
   */
  private Vocabulary $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_annif',
    'helfi_api_base',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp($processor = NULL): void {
    parent::setUp('scored_reference');

    foreach (['suggested_topics', 'taxonomy_term'] as $entityType) {
      $this->installEntitySchema($entityType);
    }

    $this->vocabulary = Vocabulary::create([
      'vid' => 'tags',
    ]);
    $this->vocabulary->save();

    NodeType::create([
      'name' => $this->randomMachineName(),
      'type' => 'test_node_bundle',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'test_keywords',
      'entity_type' => 'node',
      'type' => 'scored_entity_reference',
    ])->save();

    FieldConfig::create([
      'field_name' => 'test_keywords',
      'entity_type' => 'node',
      'bundle' => 'test_node_bundle',
      'label' => 'Test field',
    ])->save();

    $searchApiField = new Field($this->index, 'test_keywords');
    $searchApiField->setType('scored_item');
    $searchApiField->setPropertyPath('test_keywords_scored');
    $searchApiField->setLabel('Test field');
    $searchApiField->setDatasourceId('entity:node');

    $this->index->addField($searchApiField);
    $this->index->setOption('index_directly', TRUE);
    $this->index->save();
  }

  /**
   * Tests that field values are added correctly.
   */
  public function testAddFieldValues() : void {
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => $this->vocabulary->id(),
    ]);
    $term->save();

    Node::create([
      'title' => 'Test',
      'type' => 'test_node_bundle',
      'test_keywords' => [
        [
          'entity' => $term,
          'score' => 0.5,
        ],
      ],
    ])->save();

    $this->triggerPostRequestIndexing();
    $query = $this->index->query();
    $results = $query->execute();
    $values = [];

    /** @var \Drupal\search_api\Item\ItemInterface $result */
    foreach ($results as $result) {
      $field_values = $result->getField('test_keywords')->getValues();
      $values[] = $field_values;
    }

    $this->assertCount(1, $values);
    $this->assertNotEmpty($values[0]);
  }

}
