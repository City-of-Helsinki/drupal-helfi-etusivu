<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_etusivu\NewsRss\DTO\RssFeed;
use Drupal\helfi_etusivu\NewsRss\DTO\RssItem;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Represents published news items as an RSS resource.
 */
#[RestResource(
  id: 'helfi_etusivu_news_rss',
  label: new TranslatableMarkup('News: RSS'),
  uri_paths: [
    'canonical' => '/news/rss',
  ],
)]
final class NewsRssResource extends ResourceBase {

  public const int PAGE_SIZE = 15;

  /**
   * The Elastic client.
   *
   * @var \Elastic\Elasticsearch\Client
   */
  private Client $client;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private LanguageManagerInterface $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->client = $container->get('helfi_platform_config.etusivu_elastic_client');
    $instance->languageManager = $container->get(LanguageManagerInterface::class);
    return $instance;
  }

  /**
   * Parses the value from elastic response.
   *
   * @param string $key
   *   The key.
   * @param array $result
   *   The result.
   *
   * @return string|int|bool|null
   *   The return value.
   */
  private function parseSourceValue(string $key, array $result): null|string|int|bool {
    if (!isset($result['_source'][$key])) {
      return NULL;
    }
    return array_first($result['_source'][$key]);
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of published news items in the current content language.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing RSS-serializable news item data.
   */
  public function get(Request $request): ResourceResponse {
    $langcode = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();

    $query = [
      'bool' => [
        'filter' => [
          ['term' => ['search_api_language' => $langcode]],
        ],
        'must' => [
          ['term' => ['entity_type' => 'node']],
        ],
      ],
    ];

    if ($keyword = $request->query->get('keyword')) {
      $query['bool']['must'][] = [
        'bool' => [
          'minimum_should_match' => 1,
          'should' => [
            [
              'query_string' => [
                'fields' => [
                  'fulltext_title^2',
                  'field_lead_in^1.5',
                  'text_content^.1',
                ],
                'query' => "$keyword~",
              ],
            ],
            [
              'wildcard' => ['title.keyword' => "*$keyword*"],
            ],
          ],
        ],
      ];
    }

    $filters = [
      'topic' => 'news_tags',
      'neighbourhoods' => 'neighbourhoods',
      'groups' => 'news_groups',
    ];

    $params = $request->query->all();

    foreach ($filters as $queryField => $elasticField) {
      if (!isset($params[$queryField])) {
        continue;
      }
      $queryValue = $params[$queryField];

      if (!is_array($queryValue)) {
        $queryValue = [$queryValue];
      }

      // Upper bound for query filters.
      if (count($queryValue) > 100) {
        throw new BadRequestException('Too many filters.');
      }
      $query['bool']['must'][] = [
        'terms' => [$elasticField => $queryValue],
      ];
    }

    $currentPage = $request->query->get('page', 0);

    $results = [];

    try {
      $results = $this->client->search([
        'index' => 'news',
        'body' => [
          'sort' => [
            '_score',
            ['published_at' => ['order' => 'desc']],
          ],
          'query' => $query,
          'size' => self::PAGE_SIZE,
          'from' => $currentPage,
        ],
      ])->asArray();
    }
    catch (ClientResponseException | ServerResponseException) {
    }

    $cacheableMetadata = (new CacheableMetadata())
      ->addCacheableDependency($request->attributes->get(AccessAwareRouterInterface::ACCESS_RESULT))
      ->addCacheContexts(['languages:language_content', 'url.query_args'])
      // This should invalidate caches when index is updated.
      ->addCacheTags(['search_api_list:news']);

    $items = [];
    foreach ($results['hits']['hits'] ?? [] as $result) {
      $items[] = new RssItem(
        title: $this->parseSourceValue('title', $result),
        link: $this->parseSourceValue('url', $result),
        description: $this->parseSourceValue('field_lead_in', $result),
        pubDate: DrupalDateTime::createFromTimestamp($this->parseSourceValue('published_at', $result))->format('r'),
        guid: $this->parseSourceValue('uuid', $result),
      );
    }

    $feed = new RssFeed(
      title: (string) new TranslatableMarkup('News'),
      link: $request->getSchemeAndHttpHost(),
      language: $langcode,
      description: (string) new TranslatableMarkup('feed'),
      items: $items,
    );
    $response = new ResourceResponse($feed, 200, [
      'X-Total-Count' => $results['hits']['total']['value'] ?? 0,
      'X-Page-Size' => self::PAGE_SIZE,
      'X-Page' => $currentPage,
    ]);
    $response->addCacheableDependency($cacheableMetadata);

    return $response;
  }

}
