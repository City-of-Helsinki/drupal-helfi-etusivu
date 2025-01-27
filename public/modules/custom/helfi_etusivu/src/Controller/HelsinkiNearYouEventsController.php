<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\helfi_react_search\LinkedEvents;

/**
 * Events near you landing page controller.
 */
class HelsinkiNearYouEventsController extends ControllerBase {

  /**
   * Constructs a new instance.
   */
  public function __construct(protected readonly LinkedEvents $linkedEvents) {
  }

  /**
   * Returns a renderable array.
   */
  public function content() : array {
    return [
      '#attached' => [
        'drupalSettings' => [
          'helfi_events' => [
            'baseUrl' => LinkedEvents::BASE_URL,
            'data' => [
              'helfi-coordinates-based-event-list' => [
                'events_api_url' => $this->linkedEvents->getEventsRequest(),
                'field_event_location' => TRUE,
                'field_event_time' => TRUE,
                'useLocationSearch' => TRUE,
              ],
            ],
            'useExperimentalGhosts' => TRUE,
          ],
        ],
      ],
      '#theme' => 'helsinki_near_you_events',
      '#title' => $this->t(
        'Events near you',
        [],
        ['context' => 'Helsinki near you']
      )
    ];
  }
}
