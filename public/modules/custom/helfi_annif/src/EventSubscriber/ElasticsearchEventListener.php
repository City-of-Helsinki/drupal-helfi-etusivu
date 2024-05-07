<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\EventSubscriber;

use Drupal\elasticsearch_connector\Event\PrepareMappingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to Elastic indexing events.
 */
class ElasticsearchEventListener implements EventSubscriberInterface {

  /**
   * Get the elasticsearch mapping for a field.
   */
  public function prepareMapping(PrepareMappingEvent $event) : void {
    switch ($event->getMappingType()) {
      case 'ai_keyword':
        $event->setMappingConfig([
          "properties" => [
            'score' => [
              'type' => 'float',
            ],
            'uri' => [
              'type' => 'keyword',
            ],
            'label' => [
              'type' => 'keyword',
            ],
          ],
        ]);
        break;

      default:
        break;
    }
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() : array {
    return [
      'elasticsearch_connector.prepare_mapping' => 'prepareMapping',
    ];
  }

}
