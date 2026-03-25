<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\NewsRss;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\helfi_api_base\Environment\EnvironmentEnum;
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
   * The elasticsearch client.
   *
   * @var \Elastic\Elasticsearch\Client|null
   */
  protected ?Client $elasticClient = NULL;

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $container->setParameter('news_rss_elastic_index', 'news_rss_test');

    parent::register($container);
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

    $this->elasticClient = $this->container->get('helfi_platform_config.etusivu_elastic_client');
    $this->elasticClient->indices()->create([
      'index' => 'news_rss_test',
    ]);

    $startTime = time();

    foreach (['fi', 'sv', 'en'] as $language) {
      for ($i = 1; $i <= 45; $i++) {
        $tags = [100];
        $neighbourhoods = [200];
        $groups = [300];

        $id = sprintf('entity:node/%d:%s', $i, $language);

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

        $this->elasticClient->index([
          'index' => 'news_rss_test',
          'id' => $id,
          'body' => [
            'title' => ['Title ' . $language . ' ' . $i],
            'url' => ['https://app/' . $language . '/node/' . $i],
            'field_lead_in' => ['Description ' . $language . ' ' . $i],
            'published_at' => [$startTime - $i],
            'uuid' => [$this->container->get(UuidInterface::class)->generate()],
            'search_api_language' => [$language],
            'entity_type' => ['node'],
            'news_tags' => $tags,
            'neighbourhoods' => $neighbourhoods,
            'news_groups' => $groups,
          ],
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();

    // Remove test elastic index.
    $this->elasticClient->indices()->delete(['index' => 'news_rss_test']);
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

}
