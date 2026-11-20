<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\Hakuvahti;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\helfi_etusivu\Hakuvahti\NewsSearchDescriptionBuilder;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests the news search description builder.
 */
#[Group('helfi_etusivu')]
#[CoversClass(NewsSearchDescriptionBuilder::class)]
class NewsSearchDescriptionBuilderTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The term storage.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityStorageInterface>
   */
  private ObjectProphecy $storage;

  /**
   * Terms by id.
   *
   * @var array<int, \Drupal\taxonomy\TermInterface>
   */
  private array $terms = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->storage = $this->prophesize(EntityStorageInterface::class);
    $this->addTerm(1, 'news_tags', 'Traffic');
    $this->addTerm(2, 'news_tags', 'Culture');
    $this->addTerm(3, 'news_neighbourhoods', 'Kallio');
    $this->addTerm(4, 'news_group', 'Families');
    $this->addTerm(5, 'news_tags', 'Hidden', FALSE);
  }

  /**
   * Adds a mocked term.
   */
  private function addTerm(int $id, string $vid, string $label, bool $published = TRUE): void {
    $term = $this->prophesize(TermInterface::class);
    $term->bundle()->willReturn($vid);
    $term->label()->willReturn($label);
    $term->isPublished()->willReturn($published);
    $this->terms[$id] = $term->reveal();
  }

  /**
   * Gets the builder.
   *
   * @param list<int>|null $expectedIds
   *   The ids loadMultiple() must be called with once, or NULL for no call.
   */
  private function getBuilder(?array $expectedIds): NewsSearchDescriptionBuilder {
    if ($expectedIds === NULL) {
      $this->storage->loadMultiple(Argument::any())->shouldNotBeCalled();
    }
    else {
      // Keys in reverse order: the output order must not depend on them.
      $loaded = array_reverse(array_intersect_key($this->terms, array_flip($expectedIds)), TRUE);
      $this->storage->loadMultiple($expectedIds)->shouldBeCalledOnce()->willReturn($loaded);
    }

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('taxonomy_term')->willReturn($this->storage->reveal());

    $entityRepository = $this->prophesize(EntityRepositoryInterface::class);
    $entityRepository->getTranslationFromContext(Argument::type(TermInterface::class), 'fi')
      ->will(fn (array $args) => $args[0]);

    return new NewsSearchDescriptionBuilder($entityTypeManager->reveal(), $entityRepository->reveal());
  }

  /**
   * Wraps must clauses into a query.
   *
   * @param array<mixed> $must
   *   The must clauses.
   *
   * @return array<string, mixed>
   *   The query.
   */
  private static function query(array $must): array {
    return [
      'query' => [
        'bool' => [
          'must' => $must,
          'filter' => [['term' => ['search_api_language' => '<b>Injected</b>']]],
        ],
      ],
    ];
  }

  /**
   * Builds a keyword clause like the news archive client.
   *
   * @return array<string, mixed>
   *   The clause.
   */
  private static function keywordClause(string $keyword, bool $simpleQuery = TRUE, bool $wildcard = TRUE): array {
    $should = [];
    if ($simpleQuery) {
      $should[] = [
        'simple_query_string' => ['query' => mb_strtolower($keyword) . '~', 'fields' => ['fulltext_title^2']],
      ];
    }
    if ($wildcard) {
      $should[] = ['wildcard' => ['title.keyword' => "*$keyword*"]];
    }
    return ['bool' => ['should' => $should, 'minimum_should_match' => 1]];
  }

  /**
   * Data provider for testBuild().
   *
   * @return array<string, array{array<mixed>, list<int>|null, string}>
   *   The query, the ids to load and the expected description.
   */
  public static function buildProvider(): array {
    return [
      'query is a scalar' => [['query' => 'x'], NULL, ''],
      'bool is missing' => [['query' => ['match_all' => []]], NULL, ''],
      'must is a scalar' => [['query' => ['bool' => ['must' => 'x']]], NULL, ''],
      'no filters' => [self::query([['term' => ['entity_type' => 'node']]]), NULL, ''],
      'all fields in client order' => [
        self::query([
          ['term' => ['entity_type' => 'node']],
          ['terms' => ['news_tags' => [1]]],
          ['terms' => ['news_groups' => [4]]],
          ['terms' => ['neighbourhoods' => [3]]],
        ]),
        [1, 3, 4],
        'Traffic, Kallio, Families',
      ],
      'must as an object' => [
        self::query(['a' => ['terms' => ['news_tags' => [1]]]]),
        [1],
        'Traffic',
      ],
      'request order within a field' => [
        self::query([['terms' => ['news_tags' => [2, 1]]]]),
        [2, 1],
        'Culture, Traffic',
      ],
      'ids merged and deduplicated' => [
        self::query([
          ['terms' => ['news_tags' => [1, 1]]],
          ['terms' => ['news_tags' => [2, 1]]],
        ]),
        [1, 2],
        'Traffic, Culture',
      ],
      'invalid id values are dropped' => [
        self::query([['terms' => ['news_tags' => ['1', 1.0, NULL, TRUE, 0, -1, [1], 'Traffic']]]]),
        NULL,
        '',
      ],
      'wrong vocabulary' => [
        self::query([
          ['terms' => ['news_tags' => [3, 4]]],
          ['terms' => ['news_groups' => [1]]],
        ]),
        [3, 4, 1],
        '',
      ],
      'same id under two fields' => [
        self::query([
          ['terms' => ['news_tags' => [4]]],
          ['terms' => ['news_groups' => [4]]],
        ]),
        [4],
        'Families',
      ],
      'missing and unpublished terms' => [
        self::query([['terms' => ['news_tags' => [99, 5, 1]]]]),
        [99, 5, 1],
        'Traffic',
      ],
      'unknown field and other clauses are ignored' => [
        [
          'query' => [
            'bool' => [
              'must' => [
                ['terms' => ['title' => [1]]],
                ['bool' => ['must' => [['terms' => ['news_tags' => [1]]]]]],
              ],
              'filter' => [['terms' => ['news_tags' => [1]]]],
              'should' => [['terms' => ['news_tags' => [1]]]],
            ],
          ],
        ],
        NULL,
        '',
      ],
      'keyword is truncated' => [
        self::query([['terms' => ['news_tags' => [1]]], self::keywordClause('Kaupunkipyörä')]),
        [1],
        'Kaupunkip…, Traffic',
      ],
      'short keyword is kept' => [
        self::query([self::keywordClause(' Pyörä ')]),
        NULL,
        'Pyörä',
      ],
      'keyword from wildcard only' => [
        self::query([self::keywordClause('Voita iPhone', simpleQuery: FALSE)]),
        NULL,
        'Voita iPh…',
      ],
      'keyword from simple_query_string only' => [
        self::query([self::keywordClause('Voita', wildcard: FALSE)]),
        NULL,
        'voita',
      ],
      'only the first keyword is used' => [
        self::query([self::keywordClause('first'), self::keywordClause('second')]),
        NULL,
        'first',
      ],
      'empty keyword' => [
        self::query([self::keywordClause('  ')]),
        NULL,
        '',
      ],
      'control and format characters in keyword' => [
        self::query([self::keywordClause("a\r\nb\u{202E}c\u{200B}", simpleQuery: FALSE)]),
        NULL,
        'a b c',
      ],
      'keyword of control and format characters only' => [
        self::query([self::keywordClause("\u{200B}\0", simpleQuery: FALSE)]),
        NULL,
        '',
      ],
      'non-string keyword' => [
        self::query([['bool' => ['should' => [['wildcard' => ['title.keyword' => ['value' => 'x']]], 'x']]]]),
        NULL,
        '',
      ],
    ];
  }

  /**
   * Tests building the description.
   *
   * @param array<mixed> $query
   *   The query.
   * @param list<int>|null $expectedIds
   *   The ids to load.
   * @param string $expected
   *   The expected description.
   */
  #[DataProvider('buildProvider')]
  public function testBuild(array $query, ?array $expectedIds, string $expected): void {
    $this->assertSame($expected, $this->getBuilder($expectedIds)->build($query, 'fi'));
  }

  /**
   * Tests the per-field id limit.
   */
  public function testTermLimit(): void {
    $ids = range(1, NewsSearchDescriptionBuilder::MAX_TERMS_PER_FIELD + 10);
    $expectedIds = array_slice($ids, 0, NewsSearchDescriptionBuilder::MAX_TERMS_PER_FIELD);

    $builder = $this->getBuilder($expectedIds);
    $this->assertSame('Traffic, Culture', $builder->build(self::query([['terms' => ['news_tags' => $ids]]]), 'fi'));
  }

  /**
   * Tests that only whole labels are joined up to the maximum length.
   */
  public function testMaxLength(): void {
    foreach (range(1, 5) as $id) {
      $this->addTerm($id, 'news_tags', str_repeat((string) $id, 255));
    }
    $this->addTerm(6, 'news_tags', str_repeat('ä', 255));
    $this->addTerm(7, 'news_tags', str_repeat('ö', 232));

    $builder = $this->getBuilder([6, 7, 1, 2, 3, 4, 5]);
    $description = $builder->build(self::query([['terms' => ['news_tags' => [6, 7, 1, 2, 3, 4, 5]]]]), 'fi');

    // 255 + 2 + 232 + 2 + 255 + 2 + 255 = 1003 > 999, so three labels fit.
    $this->assertSame(implode(', ', [str_repeat('ä', 255), str_repeat('ö', 232), str_repeat('1', 255)]), $description);
    $this->assertLessThanOrEqual(NewsSearchDescriptionBuilder::MAX_LENGTH, mb_strlen($description));
  }

  /**
   * Tests that the description fills up to exactly the maximum length.
   */
  public function testExactMaxLength(): void {
    $this->addTerm(1, 'news_tags', str_repeat('ä', 255));
    $this->addTerm(2, 'news_tags', str_repeat('ä', 255));
    $this->addTerm(3, 'news_tags', str_repeat('ä', 255));
    $this->addTerm(4, 'news_tags', str_repeat('ö', 228));

    $description = $this->getBuilder([1, 2, 3, 4])
      ->build(self::query([['terms' => ['news_tags' => [1, 2, 3, 4]]]]), 'fi');

    $this->assertSame(NewsSearchDescriptionBuilder::MAX_LENGTH, mb_strlen($description));
    $this->assertStringEndsWith('ö', $description);
  }

}
