<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\NewsRss;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Environment\EnvironmentResolverInterface;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\helfi_etusivu\Plugin\rest\resource\NewsRssResource;
use Drupal\rest\Entity\RestResourceConfig;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\helfi_api_base\Traits\EnvironmentResolverTrait;
use Drupal\Tests\helfi_platform_config\Kernel\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Elastic\Elasticsearch\Client;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests News RSS resource.
 */
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
class NewsRssResourceTest extends KernelTestBase {

  use UserCreationTrait;
  use ApiTestTrait;
  use EnvironmentResolverTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'paragraphs',
    'entity_reference_revisions',
    'node',
    'datetime',
    'media',
    'file',
    'field',
    'rest',
    'serialization',
    'taxonomy',
    'text',
    'publication_date',
    'user',
    'helfi_etusivu',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $container->setParameter('helfi_etusivu.news_rss_elastic_index', 'news_rss_test');

    parent::register($container);
  }

  /**
   * Gets the elastic client.
   *
   * @return \Elastic\Elasticsearch\Client
   *   The elastic client.
   */
  protected function getElasticClient() : Client {
    $this->assertEquals(EnvironmentEnum::Local, $this->container->get(EnvironmentResolverInterface::class)->getActiveEnvironment()->environment);
    return $this->container->get('helfi_platform_config.etusivu_elastic_client');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setActiveProject(Project::ETUSIVU, EnvironmentEnum::Local);
    $this->installEntitySchema('rest_resource_config');
    $this->installEntitySchema('user');

    RestResourceConfig::create([
      'id' => 'helfi_etusivu_news_rss',
      'plugin_id' => 'helfi_etusivu_news_rss',
      'granularity' => 'resource',
      'configuration' => [
        'methods' => ['GET'],
        'formats' => ['rss'],
        'authentication' => ['cookie'],
      ],
    ])->save();

    // Create a dummy user before tests to make sure our actual user is not
    // UID1 and getting all permissions automatically.
    $this->createUser();

    $this->assertEquals('news_rss_test', $this->container->getParameter('helfi_etusivu.news_rss_elastic_index'));

    $this->getElasticClient()->indices()->create([
      'index' => 'news_rss_test',
    ]);
  }

  /**
   * Populates the index with dummy data.
   */
  private function populateIndex(): void {
    $startTime = time();

    foreach (['fi', 'sv', 'en'] as $language) {
      for ($i = 1; $i <= 45; $i++) {
        $tags = $neighbourhoods = $groups = [];

        // Every second item should have a group with 302 term ID.
        if ($i % 2 === 0) {
          $groups[] = 302;
        }

        // Every third item should have all terms with x03 term ID.
        if ($i % 3 === 0) {
          $tags[] = 103;
          $neighbourhoods[] = 203;
          $groups[] = 303;
        }

        // Every 14th item should have a tag with id 114.
        if ($i % 14 === 0) {
          $tags[] = 114;
        }
        $this->createElasticIndexItem(
          sprintf('entity:node/%d:%s', $i, $language),
          "Title $language $i",
          "https://app/$language/node/$i",
          "Description $language $i",
          $startTime - $i,
          $this->container->get(UuidInterface::class)->generate(),
          $language,
          $tags,
          $neighbourhoods,
          $groups,
        );
      }
    }
    // We index 45 documents in 3 languages. Allow indexing take
    // up to ~120 seconds.
    $this->validateElasticIndex(135, 60);
  }

  /**
   * Validates that the elastic index is populated.
   *
   * @param int $expectedResults
   *   The expected number of results.
   * @param int $maxLoops
   *   The maximum loops. Each loops takes ~2 seconds.
   */
  private function validateElasticIndex(int $expectedResults, int $maxLoops): void {
    // Elastic takes some time to index data. Make sure everything is up to
    // date before running tests.
    for ($i = 1; $i <= $maxLoops; $i++) {
      $response = $this->getElasticClient()->search([
        'index' => 'news_rss_test',
      ])->asArray();
      $hits = $response['hits']['total']['value'] ?? 0;

      if ($hits === $expectedResults) {
        break;
      }
      sleep(2);
    }
  }

  /**
   * Constructs a new Elastic item.
   *
   * @param string|null $id
   *   The ID.
   * @param string|null $title
   *   The title.
   * @param string|null $url
   *   The URL.
   * @param string|null $description
   *   The description.
   * @param int|null $publishedAt
   *   The published at.
   * @param string|null $uuid
   *   The uuid.
   * @param string|null $language
   *   The language.
   * @param array|null $tags
   *   The tags.
   * @param array|null $neighbourhoods
   *   The neighbourhoods.
   * @param array|null $groups
   *   The groups.
   */
  protected function createElasticIndexItem(
    ?string $id = NULL,
    ?string $title = NULL,
    ?string $url = NULL,
    ?string $description = NULL,
    ?int $publishedAt = NULL,
    ?string $uuid = NULL,
    ?string $language = NULL,
    ?array $tags = NULL,
    ?array $neighbourhoods = NULL,
    ?array $groups = NULL,
  ): void {
    $this->getElasticClient()->index([
      'index' => 'news_rss_test',
      'id' => $id,
      'body' => [
        'title' => [$title],
        'url' => [$url],
        'field_lead_in' => [$description],
        'published_at' => [$publishedAt],
        'uuid' => [$uuid],
        'search_api_language' => [$language],
        'entity_type' => ['node'],
        'news_tags' => $tags,
        'neighbourhoods' => $neighbourhoods,
        'news_groups' => $groups,
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Remove test elastic index.
    $response = $this->getElasticClient()->indices()->delete(['index' => 'news_rss_test']);
    $this->assertEquals(200, $response->getStatusCode());

    parent::tearDown();
  }

  /**
   * Validates the given XML.
   *
   * @param string $xml
   *   The XML to validate.
   *
   * @return \DOMDocument
   *   The loaded XML document.
   */
  private function assertXml(string $xml): \DOMDocument {
    $dom = new \DOMDocument();
    $dom->loadXML($xml);
    $errors = libxml_get_errors();
    libxml_clear_errors();
    $this->assertCount(0, $errors);
    return $dom;
  }

  /**
   * Asserts that the RSS <item> contains all expected values.
   *
   * @param \DOMElement $node
   *   The parent node.
   * @param int $index
   *   The item index.
   * @param string $language
   *   The expected language.
   */
  private function assertRssItem(\DOMElement $node, int $index, string $language): void {
    $this->assertEquals("Title en $index", $node->getElementsByTagName('title')->item(0)->nodeValue);
    $this->assertEquals("Description en $index", $node->getElementsByTagName('description')->item(0)->nodeValue);
    $this->assertEquals("https://app/$language/node/$index", $node->getElementsByTagName('link')->item(0)->nodeValue);
    $this->assertNotEmpty($node->getElementsByTagName('pubDate')->item(0)->nodeValue);
    $this->assertNotEmpty($node->getElementsByTagName('guid')->item(0)->nodeValue);
  }

  /**
   * Tests route permissions.
   */
  #[Test]
  public function testAccess(): void {
    $this->createElasticIndexItem(
      id: 'entity:node/1:en',
      title: 'Title 1',
      url: 'https://app',
      description: '',
      publishedAt: 0,
      uuid: '123',
      language: 'en',
      tags: [],
      neighbourhoods: [],
      groups: [],
    );
    $this->validateElasticIndex(1, 5);

    $request = $this->getMockedRequest('/news/rss');
    $response = $this->processRequest($request);

    $this->assertEquals(403, $response->getStatusCode());

    $this->setUpCurrentUser(permissions: ['restful get helfi_etusivu_news_rss']);

    $response = $this->processRequest($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertXml($response->getContent());
  }

  /**
   * Tests pager.
   */
  #[Test]
  public function testPager(): void {
    $this->populateIndex();
    $this->setUpCurrentUser(permissions: ['restful get helfi_etusivu_news_rss']);

    $itemNumber = 1;
    // Paginate through all 45 items.
    for ($page = 0; $page <= 2; $page++) {
      $request = $this->getMockedRequest('/news/rss', parameters: ['page' => $page]);
      $response = $this->processRequest($request);

      $this->assertEquals(200, $response->getStatusCode());
      // Make sure content-type includes charset.
      $this->assertEquals('application/rss+xml; charset=UTF-8', $response->headers->get('Content-Type'));

      $this->assertEquals(45, $response->headers->get('X-Total-Count'));
      $this->assertEquals(NewsRssResource::PAGE_SIZE, $response->headers->get('X-Page-Size'));
      $this->assertEquals($page, $response->headers->get('X-Page'));
      $content = $response->getContent();
      $dom = $this->assertXml($content);
      $items = $dom->getElementsByTagName('item');
      $this->assertEquals(15, $items->length);

      for ($i = 0; $i < 15; $i++) {
        $item = $items->item($i);

        $this->assertRssItem($item, $itemNumber++, 'en');
      }
    }
  }

  /**
   * Tests query filters.
   */
  #[Test]
  public function testFilters(): void {
    $this->populateIndex();
    $this->setUpCurrentUser(permissions: ['restful get helfi_etusivu_news_rss']);

    $request = $this->getMockedRequest('/news/rss', parameters: ['keyword' => 'Description en']);
    $response = $this->processRequest($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals(45, $response->headers->get('X-Total-Count'));

    $expected = [
      'topic' => ['id' => 103, 'count' => 15],
      'neighbourhoods' => ['id' => 203, 'count' => 15],
      'groups' => ['id' => 302, 'count' => 22],
      // Make sure invalid filter is ignored.
      'invalid_filter' => ['id' => 1, 'count' => 45],
    ];

    foreach ($expected as $field => $value) {
      ['id' => $id, 'count' => $count] = $value;
      $request = $this->getMockedRequest('/news/rss', parameters: [$field => $id]);
      $response = $this->processRequest($request);
      $this->assertEquals(200, $response->getStatusCode());
      $this->assertEquals($count, $response->headers->get('X-Total-Count'));
    }

    $request = $this->getMockedRequest('/news/rss', parameters: [
      'groups' => ['303', '302'],
      'topic' => '114',
    ]);
    $response = $this->processRequest($request);
    $this->assertEquals(200, $response->getStatusCode());
    // Only three items should have group 303 OR 302 AND topic 114.
    $this->assertEquals(3, $response->headers->get('X-Total-Count'));

    $request = $this->getMockedRequest('/news/rss', parameters: [
      'groups' => ['303'],
      'topic' => '114',
    ]);
    $response = $this->processRequest($request);
    $this->assertEquals(200, $response->getStatusCode());
    // Only one item should have group 303 AND topic 114.
    $this->assertEquals(1, $response->headers->get('X-Total-Count'));
  }

  /**
   * Tests RSS with invalid elastic values.
   */
  public function testEmptyFieldValues(): void {
    $this->setUpCurrentUser(permissions: ['restful get helfi_etusivu_news_rss']);
    // Make sure we have at least one valid item so Elastic creates the correct
    // field mapping.
    $this->createElasticIndexItem(
      id: 'entity:node/1:en',
      title: 'Title 1',
      url: 'https://app',
      description: '',
      publishedAt: 0,
      uuid: '123',
      language: 'en',
      tags: [],
      neighbourhoods: [],
      groups: [],
    );
    // Create an empty elastic item, this should be completely ignored due to
    // 'search_api_language' filter.
    $this->createElasticIndexItem();
    // Create an item with bare minimum values.
    $this->createElasticIndexItem(
      publishedAt: time(),
      language: 'en',
    );
    $this->validateElasticIndex(3, 5);

    $request = $this->getMockedRequest('/news/rss');
    $response = $this->processRequest($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals(2, $response->headers->get('X-Total-Count'));
  }

}
