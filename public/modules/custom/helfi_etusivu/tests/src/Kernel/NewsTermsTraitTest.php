<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * @covers \Drupal\helfi_etusivu\NewsTermsTrait
 *
 * @group helfi_etusivu
 */
class NewsTermsTraitTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'field',
    'user',
    'system',
    'text',
    'filter',
    'news_terms_trait_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');

    $this->installConfig(['node']);

    $this->installEntitySchema('node_type');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * Provides a test node class that uses the trait.
   */
  protected function getTestNode(array $term_ids = [], bool $published = TRUE): Node {
    $values = [
      'type' => 'test_node_bundle',
      'title' => 'Test news',
      'field_news_item_tags' => array_map(fn($id) => ['target_id' => $id], $term_ids),
      'status' => $published,
    ];

    $node = Node::create($values);
    $node->save();

    return $node;
  }

  /**
   * Test getNewsTerms().
   */
  public function testGetNewsTerms(): void {
    // Create taxonomy vocabulary.
    Vocabulary::create([
      'vid' => 'news_tags',
      'name' => 'News Tags',
    ])->save();

    // Create content type and attach term reference field.
    NodeType::create(['type' => 'test_node_bundle', 'name' => 'News'])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_news_item_tags',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
      'cardinality' => -1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_news_item_tags',
      'entity_type' => 'node',
      'bundle' => 'test_node_bundle',
      'label' => 'News Item Tags',
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => ['news_tags' => 'news_tags'],
        ],
      ],
    ])->save();

    // Create terms.
    $term1 = Term::create([
      'name' => 'Tag One',
      'vid' => 'news_tags',
    ]);
    $term1->save();

    $term2 = Term::create([
      'name' => 'Tag Two',
      'vid' => 'news_tags',
    ]);
    $term2->save();

    $term_ids = [$term1->id(), $term2->id()];
    $node = $this->getTestNode($term_ids);

    /** @var \Drupal\news_terms_trait_test\Entity\NewsTermsTestNode $node */
    $this->assertEquals(implode(',', $term_ids), $node->getNewsTerms());

    // Test unpublished node returns empty string.
    $unpublished_node = $this->getTestNode($term_ids, FALSE);

    /** @var \Drupal\news_terms_trait_test\Entity\NewsTermsTestNode $unpublished_node */
    $this->assertSame('', $unpublished_node->getNewsTerms());

    $node = Node::create(['title' => 'No tags node', 'type' => 'no_tags']);
    $node->save();

    /** @var \Drupal\news_terms_trait_test\Entity\NewsTermsTestNode $node */
    $this->assertSame('', $node->getNewsTerms());
  }

  /**
   * Test getTaxonomyTermsWithArchiveLinks().
   */
  public function testGetTaxonomyTermsWithArchiveLinks(): void {
    $vocabularies = [
      'item_tags' => 'Topics',
      'neighbourhoods' => 'Neighbourhoods',
      'groups' => 'Groups',
    ];

    $terms = [];

    foreach ($vocabularies as $vid => $name) {
      Vocabulary::create(['vid' => $vid, 'name' => $name])->save();

      foreach (['one', 'two'] as $label) {
        $term = Term::create([
          'name' => "$vid $label",
          'vid' => $vid,
        ]);

        $term->save();

        $terms[$vid . '_' . $label] = [
          'field' => "field_news_$vid",
          'term_name' => "$vid $label",
          'term_id' => $term->id(),
        ];
      }
    }

    // Create content type and attach term reference fields.
    NodeType::create(['type' => 'test_node_bundle', 'name' => 'News'])->save();

    $fields = [
      'field_news_item_tags' => 'news_tags',
      'field_news_neighbourhoods' => 'neighbourhoods',
      'field_news_groups' => 'groups',
    ];

    foreach ($fields as $field_name => $target_bundle) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => 'entity_reference',
        'settings' => [
          'target_type' => 'taxonomy_term',
        ],
        'cardinality' => -1,
      ])->save();

      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'bundle' => 'test_node_bundle',
        'label' => ucfirst(str_replace('_', ' ', $field_name)),
        'settings' => [
          'handler' => 'default:taxonomy_term',
          'handler_settings' => [
            'target_bundles' => [$target_bundle => $target_bundle],
          ],
        ],
      ])->save();
    }

    // Create node with test data.
    $values = [
      'type' => 'test_node_bundle',
      'title' => 'Test news item',
      'status' => TRUE,
    ];

    foreach ($terms as $term_data) {
      $values[$term_data['field']][] = ['target_id' => $term_data['term_id']];
    }

    $node = Node::create($values);

    $expected = [
      'field_news_item_tags' => [
        [
          'name' => 'item_tags one',
          'url' => '/en/news/search-for-news?topic[0]=' . $terms['item_tags_one']['term_id'],
        ],
        [
          'name' => 'item_tags two',
          'url' => '/en/news/search-for-news?topic[0]=' . $terms['item_tags_two']['term_id'],
        ],
      ],
      'field_news_neighbourhoods' => [
        [
          'name' => 'neighbourhoods one',
          'url' => '/en/news/search-for-news?neighbourhoods[0]=' . $terms['neighbourhoods_one']['term_id'],
        ],
        [
          'name' => 'neighbourhoods two',
          'url' => '/en/news/search-for-news?neighbourhoods[0]=' . $terms['neighbourhoods_two']['term_id'],
        ],
      ],
      'field_news_groups' => [
        [
          'name' => 'groups one',
          'url' => '/en/news/search-for-news?groups[0]=' . $terms['groups_one']['term_id'],
        ],
        [
          'name' => 'groups two',
          'url' => '/en/news/search-for-news?groups[0]=' . $terms['groups_two']['term_id'],
        ],
      ],
    ];

    /** @var \Drupal\news_terms_trait_test\Entity\NewsTermsTestNode $node */
    $result = $node->getTaxonomyTermsWithArchiveLinks();
    $this->assertEquals($expected, $result);
  }

}
