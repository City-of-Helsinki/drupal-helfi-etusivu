<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\EventSubscriber\HakuvahtiSubscriptionSubscriber;
use Drupal\helfi_hakuvahti\Event\SubscriptionAlterEvent;
use Drupal\helfi_hakuvahti\Hakuvahti;
use Drupal\helfi_hakuvahti\HakuvahtiInterface;
use Drupal\helfi_hakuvahti\HakuvahtiRequest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the news archive hakuvahti subscription subscriber.
 */
#[Group('helfi_etusivu')]
#[CoversClass(HakuvahtiSubscriptionSubscriber::class)]
#[RunTestsInSeparateProcesses]
class HakuvahtiSubscriptionSubscriberTest extends KernelTestBase {

  use ApiTestTrait;
  use UserCreationTrait;

  private const string CLIENT_DESCRIPTION = '<a href="https://example.com">x</a>';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'taxonomy',
    'language',
    'content_translation',
    'elasticsearch_connector',
    'helfi_api_base',
    'diff',
    'big_pipe',
    'search_api',
    'file',
    'helfi_tunnistamo',
    'openid_connect',
    'externalauth',
    'helfi_hakuvahti',
    'helfi_etusivu',
  ];

  /**
   * The id of the 'Traffic' news tag.
   */
  private int $trafficTid;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('taxonomy_term');

    Vocabulary::create(['vid' => 'news_tags', 'name' => 'news_tags'])->save();
    $term = Term::create(['vid' => 'news_tags', 'name' => 'Traffic']);
    $term->save();
    $this->trafficTid = (int) $term->id();
  }

  /**
   * Creates a request.
   */
  private function createRequest(string $elasticQuery): HakuvahtiRequest {
    return new HakuvahtiRequest([
      'email' => 'test@example.com',
      'lang' => 'en',
      'siteId' => 'etusivu',
      'query' => '/en/news?topic[0]=1',
      'elasticQuery' => $elasticQuery,
      'elasticQueryAtv' => FALSE,
    ], self::CLIENT_DESCRIPTION);
  }

  /**
   * Encodes a query.
   *
   * @param array<mixed> $query
   *   The query.
   */
  private static function encode(array $query): string {
    return base64_encode(json_encode($query, JSON_THROW_ON_ERROR));
  }

  /**
   * Dispatches the alter event.
   */
  private function dispatch(SubscriptionAlterEvent $event): void {
    $this->container->get(EventDispatcherInterface::class)->dispatch($event);
  }

  /**
   * Tests that the client description is replaced.
   */
  public function testDescription(): void {
    $query = ['query' => ['bool' => ['must' => [['terms' => ['news_tags' => [$this->trafficTid]]]]]]];
    $request = $this->createRequest(self::encode($query));
    $event = new SubscriptionAlterEvent($request, HakuvahtiSubscriptionSubscriber::CONFIG_ID);

    $this->dispatch($event);

    $this->assertEquals(
      array_merge(get_object_vars($request), ['searchDescription' => 'Traffic']),
      get_object_vars($event->getHakuvahtiRequest()),
    );
  }

  /**
   * Tests that an undecodable query is rejected.
   */
  public function testInvalidQuery(): void {
    $event = new SubscriptionAlterEvent($this->createRequest('not-base64!!'), HakuvahtiSubscriptionSubscriber::CONFIG_ID);

    $this->expectException(\InvalidArgumentException::class);
    $this->dispatch($event);
  }

  /**
   * Tests that other configs are left alone.
   */
  public function testOtherConfig(): void {
    foreach (['default', NULL] as $configId) {
      $request = $this->createRequest('not-base64!!');
      $event = new SubscriptionAlterEvent($request, $configId);

      $this->dispatch($event);

      $this->assertSame($request, $event->getHakuvahtiRequest());
    }
  }

  /**
   * Tests the subscribe endpoint with the news archive config.
   */
  public function testSubscribeEndpoint(): void {
    $this->config('helfi_hakuvahti.settings')->set('base_url', 'https://example.com')->save();
    $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('hakuvahti_config')
      ->create(['id' => HakuvahtiSubscriptionSubscriber::CONFIG_ID, 'label' => 'News', 'site_id' => 'etusivu'])
      ->save();
    $history = [];
    $client = $this->createMockHistoryMiddlewareHttpClient($history, [new Response(200)]);
    $this->container->set(HakuvahtiInterface::class, new Hakuvahti($client, $this->container->get(ConfigFactoryInterface::class)));
    $this->setUpCurrentUser(permissions: ['access content']);

    $query = ['query' => ['bool' => ['must' => [['terms' => ['news_tags' => [$this->trafficTid]]]]]]];
    $body = [
      'email' => 'test@example.com',
      'query' => '/en/news?topic[0]=1',
      'searchDescription' => self::CLIENT_DESCRIPTION,
    ];
    $url = Url::fromRoute('helfi_hakuvahti.subscribe', options: ['query' => ['config' => HakuvahtiSubscriptionSubscriber::CONFIG_ID]])->toString();

    $response = $this->processRequest($this->getMockedRequest($url, 'POST', document: ['elasticQuery' => 'not-base64!!'] + $body));
    $this->assertSame(400, $response->getStatusCode());
    $this->assertCount(0, $history);

    $response = $this->processRequest($this->getMockedRequest($url, 'POST', document: ['elasticQuery' => self::encode($query)] + $body));
    $this->assertSame(200, $response->getStatusCode());
    $this->assertCount(1, $history);
    $sent = json_decode((string) $history[0]['request']->getBody(), TRUE);
    $this->assertSame('Traffic', $sent['search_description']);
    $this->assertSame('etusivu', $sent['site_id']);
  }

}
