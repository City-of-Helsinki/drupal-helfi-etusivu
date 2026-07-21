<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\ServiceMapInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\Client;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\LazyBuilder;

/**
 * Events near you landing page controller.
 */
final class EventsController extends HtmxController {

  /**
   * Constructs a new instance.
   */
  public function __construct(
    private readonly LazyBuilder $lazyBuilder,
    ServiceMapInterface $serviceMap,
    FormBuilderInterface $formBuilder,
    LanguageManagerInterface $languageManager,
  ) {
    parent::__construct($serviceMap, $formBuilder, $languageManager);
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
   * {@inheritdoc}
   */
  protected function buildHtmxResults(Address $address, string $langcode, ?int $limit = NULL): array {
    return $this->lazyBuilder->build($address, $langcode, $limit);
  }

  /**
   * Returns a renderable array.
   */
  public function content() : array {
    $langcode = $this->languageManager
      ->getCurrentLanguage()
      ->getId();

    return [
      '#attached' => [
        'drupalSettings' => [
          'helfi_events' => [
            'baseUrl' => Client::BASE_URL,
            'data' => [
              'helfi-coordinates-based-event-list' => [
                'events_api_url' => Client::getUri($langcode, [], 3),
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
