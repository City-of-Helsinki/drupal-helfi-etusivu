<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\EventSubscriber;

use Drupal\Component\Utility\NestedArray;
use Drupal\elasticsearch_connector\Event\AlterSettingsEvent;
use Drupal\elasticsearch_connector\Event\FieldMappingEvent;
use Drupal\elasticsearch_connector\Event\IndexParamsEvent;
use Drupal\elasticsearch_connector\Event\IndexPreCreateEvent;
use Drupal\helfi_search\QueryBuilder;
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
      IndexPreCreateEvent::class => 'addPercolatorField',
      IndexParamsEvent::class => 'addPromotionPercolatorQuery',
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
   * Add the percolator field to the search_promotions index mapping.
   */
  public function addPercolatorField(IndexPreCreateEvent $event): void {
    if ($event->getIndex()->id() !== 'search_promotions') {
      return;
    }
    $params = $event->getParams();
    $params['body']['mappings']['properties']['query'] = ['type' => 'percolator'];
    $event->setParams($params);
  }

  /**
   * Store a percolator query on each indexed promotion document.
   *
   * @param \Drupal\elasticsearch_connector\Event\IndexParamsEvent $event
   *   The index params event.
   */
  public function addPromotionPercolatorQuery(IndexParamsEvent $event): void {
    if ($event->getOriginalIndexId() !== 'search_promotions') {
      return;
    }
    $params = $event->getParams();
    // The bulk body alternates action lines (['index' => [...]]) with the
    // document source; only the latter carry the indexed fields. Write to
    // $params['body'][$i] directly: a `foreach (... ?? [] as &$line)` would
    // iterate a temporary copy and the writes would never reach $params.
    foreach ($params['body'] ?? [] as $i => $line) {
      if (isset($line['index']) || empty($line['keywords'])) {
        continue;
      }
      $language = $line['search_api_language'][0] ?? 'en';
      $params['body'][$i]['query'] = QueryBuilder::buildPromotionPercolatorQuery($line['keywords'], $language);
    }
    $event->setParams($params);
  }

}
