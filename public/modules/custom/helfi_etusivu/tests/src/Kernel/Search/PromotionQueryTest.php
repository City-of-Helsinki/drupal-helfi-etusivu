<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Search;

use Drupal\elasticsearch_connector\Event\FieldMappingEvent;
use Drupal\helfi_etusivu\EventSubscriber\ElasticsearchEventSubscriber;
use Drupal\helfi_search\QueryBuilder;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Item\FieldInterface;
use Drupal\Tests\helfi_etusivu\Kernel\EtusivuElasticTestBase;
use Elastic\Elasticsearch\Response\Elasticsearch;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test promotion query integration with the helfi_search module.
 *
 * This test is tightly coupled with the helfi_search module. However,
 * executing it in the etusivu context makes sense because the elasticsearch
 * index that the search uses is configured here.
 */
#[Group('helfi_etusivu')]
class PromotionQueryTest extends EtusivuElasticTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_search',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getIndexDefinition(): array {
    return [
      'mappings' => [
        'properties' => [
          'keywords' => $this->promotionKeywordsMapping(),
          // The promotion query filters on this with a `term` clause.
          'search_api_language' => ['type' => 'keyword'],
          // The search-time query percolates the user input against it.
          // https://www.elastic.co/docs/reference/query-languages/query-dsl/query-dsl-percolate-query
          'query' => ['type' => 'percolator'],
        ],
      ],
    ];
  }

  /**
   * Tests that a keyword matches when it appears inside a longer query.
   */
  public function testPromotionQuery(): void {
    $this->indexPromotion('sauna', 'sauna', 'fi');
    $this->indexPromotion('palvelu', 'Taloushallintopalvelu', 'fi');
    $this->indexPromotion('camping', 'Rastila Camping hinnasto', 'fi');
    $this->refreshIndex();

    // Exact keyword match.
    $this->assertSame(['sauna'], $this->matchedPromotions('sauna', 'fi'));

    // Case-insensitive match.
    $this->assertSame(['sauna'], $this->matchedPromotions('MISSÄ SAUNA', 'fi'));

    // "saunat" -> "sauna", so it must match.
    $this->assertSame(['sauna'], $this->matchedPromotions('Helsingin saunat', 'fi'));

    // Word boundaries are respected: "palvelu" is only a substring of the
    // compound words, never its own token, so these must NOT match the
    // "Taloushallintopalvelu" keyword.
    $this->assertSame([], $this->matchedPromotions('kaupungin neuvontapalvelut', 'fi'));

    // A query in a different language must not surface fi promotions.
    $this->assertSame([], $this->matchedPromotions('sauna', 'sv'));

    // The query matches some keywords keyword, but the result would irrelevant.
    $this->assertSame([], $this->matchedPromotions('Yrjönkadun uimahalli hinnasto', 'fi'));

    // Matches if query contains extra words and inflected words matches.
    $this->assertSame(['camping'], $this->matchedPromotions('Mistä löydän rastilan camping alueen hinnaston', 'fi'));
  }

  /**
   * Runs buildPromotionQuery against the live index and returns matched titles.
   *
   * @param string $query
   *   The search query string.
   * @param string $language
   *   The language code.
   *
   * @return list<string>
   *   The titles of the matched promotions.
   */
  private function matchedPromotions(string $query, string $language): array {
    $built = $this->container->get(QueryBuilder::class)
      ->buildPromotionQuery($query, $language);

    $response = $this->getElasticClient()->search([
      // The builder hardcodes the production index name; point it at the
      // ephemeral test index instead.
      'index' => $this->indexName,
      'body' => $built['body'],
    ]);
    assert($response instanceof Elasticsearch);
    $response = $response->asArray();

    return array_map(
      static fn (array $hit): string => $hit['_source']['title'][0],
      $response['hits']['hits'],
    );
  }

  /**
   * Indexes a single promotion document.
   *
   * @param string $title
   *   A human-readable title used to identify the document in assertions.
   * @param string $keyword
   *   The keyword that should trigger the promotion.
   * @param string $language
   *   The language code.
   */
  private function indexPromotion(string $title, string $keyword, string $language): void {
    $this->indexItem(
      body: [
        'title' => [$title],
        'description' => ['Description for ' . $title],
        'link' => ['https://example.com/' . $title],
        'keywords' => [$keyword],
        'search_api_language' => [$language],
        // Mirror what ElasticsearchEventSubscriber stores in production.
        'query' => QueryBuilder::buildPromotionPercolatorQuery([$keyword], $language),
      ],
    );
  }

  /**
   * Builds the keywords field mapping using the production subscriber.
   *
   * @return array<string, mixed>
   *   The Elasticsearch mapping for the keywords field.
   */
  private function promotionKeywordsMapping(): array {
    $index = $this->prophesize(Index::class);
    $index->id()->willReturn('search_promotions');

    $field = $this->prophesize(FieldInterface::class);
    $field->getIndex()->willReturn($index->reveal());
    $field->getFieldIdentifier()->willReturn('keywords');

    $event = new FieldMappingEvent($field->reveal(), []);
    // The subscriber has no service dependencies; instantiate it directly so
    // the mapping stays in sync without enabling the whole module graph.
    (new ElasticsearchEventSubscriber())->mapPromotionFields($event);

    return $event->getParam();
  }

}
