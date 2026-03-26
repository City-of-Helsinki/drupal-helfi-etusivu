<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\EventSubscriber;

use Drupal\Component\Utility\NestedArray;
use Drupal\elasticsearch_connector\Event\AlterSettingsEvent;
use Drupal\elasticsearch_connector\Event\FieldMappingEvent;
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

}
