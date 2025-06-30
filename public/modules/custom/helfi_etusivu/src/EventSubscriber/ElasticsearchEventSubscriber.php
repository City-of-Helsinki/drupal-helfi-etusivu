<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\EventSubscriber;

use Drupal\Component\Utility\NestedArray;
use Drupal\elasticsearch_connector\Event\AlterSettingsEvent;
use Drupal\elasticsearch_connector\Event\QueryParamsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * {@inheritdoc}
 */
class ElasticsearchEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      AlterSettingsEvent::class => 'prepareIndices',
      QueryParamsEvent::class => 'prepareQueryParams',
    ];
  }

  /**
   * Method to prepare index.
   *
   * @param \Drupal\elasticsearch_connector\Event\AlterSettingsEvent $event
   *   The PrepareIndex event.
   */
  public function prepareIndices(AlterSettingsEvent $event): void {
    $indexName = $event->getIndex()->id();
    $finnishIndices = [
      'news',
    ];
    if (in_array($indexName, $finnishIndices)) {
      $event->setSettings(NestedArray::mergeDeep(
        $event->getSettings(),
        [
          'analysis' => [
            'analyzer' => [
              'default' => [
                'type' => 'finnish',
              ],
            ],
          ],
        ],
      ));
    }
  }

  /**
   * Modify query to match the one we use in react app.
   * 
   * @param \Drupal\elasticsearch_connector\Event\QueryParamsEvent $event
   *   The QueryParams event.
   */
  public function prepareQueryParams(QueryParamsEvent $event): void {
    [$index, $body] = array_values($event->getParams());

    if ($index !== 'news' || empty($body['query']['bool']['must']['query_string'])) {
      return;
    }

    $must = $body['query']['bool']['must'];
    $query = $must['query_string']['query'];
    $keyword = str_split($query, strlen($query) - 1)[0];

    $body['query']['bool']['should'] = [$must];
    unset($body['query']['bool']['must']);
    $body['query']['bool']['minimum_should_match'] = 1;
    $body['query']['bool']['should'][] = [
      'wildcard' => [
        'title.keyword' => '*' . $keyword . '*',
      ],
    ];

    $event->setParams([
      'index' => $index,
      'body' => $body,
    ]);
  }
}
