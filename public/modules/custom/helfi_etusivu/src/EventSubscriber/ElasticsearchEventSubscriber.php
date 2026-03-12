<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\EventSubscriber;

use Drupal\Component\Utility\NestedArray;
use Drupal\elasticsearch_connector\Event\AlterSettingsEvent;
use Drupal\elasticsearch_connector\Event\FieldMappingEvent;
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
      FieldMappingEvent::class => 'mapPromotionFields',
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
   * Map the keywords field on the search_promotions index to text analyzers.
   *
   * @param \Drupal\elasticsearch_connector\Event\FieldMappingEvent $event
   *   The FieldMapping event.
   */
  public function mapPromotionFields(FieldMappingEvent $event): void {
    $field = $event->getField();
    if ($field->getIndex()->id() !== 'search_promotions' || $field->getFieldIdentifier() !== 'keywords') {
      return;
    }
    $event->setParam([
      'type' => 'text',
      'fields' => [
        'fi' => ['type' => 'text', 'analyzer' => 'finnish'],
        'sv' => ['type' => 'text', 'analyzer' => 'swedish'],
        'en' => ['type' => 'text', 'analyzer' => 'english'],
      ],
    ]);
  }

  /**
   * Modify query to match the one we use in react app.
   *
   * @param \Drupal\elasticsearch_connector\Event\QueryParamsEvent $event
   *   The QueryParams event.
   */
  public function prepareQueryParams(QueryParamsEvent $event): void {
    $params = $event->getParams();

    if ($event->getIndexName() !== 'news' || empty($params['body']['query']['bool']['must']['query_string'])) {
      return;
    }

    $must = $params['body']['query']['bool']['must'];
    $query = $must['query_string']['query'];
    $keyword = str_split($query, strlen($query) - 1)[0];

    $params['body']['query']['bool']['should'] = [$must];
    unset($params['body']['query']['bool']['must']);
    $params['body']['query']['bool']['minimum_should_match'] = 1;
    $params['body']['query']['bool']['should'][] = [
      'wildcard' => [
        'title.keyword' => '*' . $keyword . '*',
      ],
    ];

    $event->setParams([
      'index' => $event->getIndexName(),
      'body' => $params['body'],
    ]);
  }

}
