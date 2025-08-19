<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents;

/**
 * Events near you landing page controller.
 */
class EventsController extends ControllerBase {

  /**
   * Constructs a new instance.
   */
  public function __construct(protected readonly LinkedEvents $linkedEvents) {
  }

  /**
   * A controller callback for events route that provides the route title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated route title.
   */
  public function getTitle() {
    return $this->t('Events near you', [], ['context' => 'Helsinki near you']);
  }

  /**
   * Returns a renderable array.
   */
  public function content() : array {
    $events_url = $this->linkedEvents->getEventsRequest();

    return [
      '#attached' => [
        'drupalSettings' => [
          'helfi_events' => [
            'baseUrl' => LinkedEvents::BASE_URL,
            'data' => [
              'helfi-coordinates-based-event-list' => [
                'events_api_url' => $events_url,
                'field_event_count' => 10,
                'field_event_location' => TRUE,
                'field_event_time' => TRUE,
                'field_free_events' => TRUE,
                'field_remote_events' => TRUE,
                'places' => [],
                'hideHeading' => TRUE,
                'useFullLocationFilter' => TRUE,
                'useFullTopicsFilter' => TRUE,
                'useLocationSearch' => TRUE,
                'useTargetGroupFilter' => TRUE,
              ],
            ],
            'seeAllButtonOverride' => $this->t(
              'Search for more events on the Events website',
              [],
              ['context' => 'Helsinki near you events search']
            ),
            'useExperimentalGhosts' => TRUE,
          ],
        ],
      ],
      '#theme' => 'helsinki_near_you_events',
    ];
  }

}
