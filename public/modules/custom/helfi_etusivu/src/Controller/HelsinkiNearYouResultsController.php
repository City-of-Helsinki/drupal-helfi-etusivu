<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\external_entities\Entity\Query\External\Query;
use Drupal\helfi_etusivu\Enum\InternalSearchLink;
use Drupal\helfi_etusivu\Enum\ServiceMapLink;
use Drupal\helfi_etusivu\Servicemap;
use Drupal\helfi_paragraphs_news_list\Entity\ExternalEntity\Term;
use Drupal\helfi_react_search\LinkedEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Helsinki near you controller.
 */
class HelsinkiNearYouResultsController extends ControllerBase {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_etusivu\Servicemap $servicemap
   *   The servicemap service.
   * @param \Drupal\helfi_react_search\LinkedEvents $linkedEvents
   *   The linked events service.
   */
  public function __construct(
    protected readonly Servicemap $servicemap,
    protected readonly LinkedEvents $linkedEvents,
  ) {
  }

  /**
   * Returns a renderable array.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   A renderable array.
   */
  public function content(Request $request) : array|RedirectResponse {
    $address = $request->query->get('q');
    $return_url = Url::fromRoute('helfi_etusivu.helsinki_near_you');

    if (!$address) {
      $this->messenger()->addError($this->t('Please enter an address', [], ['context' => 'Helsinki near you']));
      return $this->redirect('helfi_etusivu.helsinki_near_you');
    }
    $address = Xss::filter($address);
    $addressData = $this->servicemap->getAddressData(urldecode($address));

    if (!$addressData) {
      $this->messenger()->addError(
        $this->t(
          'The address you input yielded no results. You may want to try a different address.',
          [],
          ['context' => 'Helsinki near you']
        )
      );
      return $this->redirect('helfi_etusivu.helsinki_near_you');
    }

    $addressName = $this->resolveTranslation($addressData['address_translations']);

    $neighborhoods = $this->getNearbyNewsNeighbourhoods($addressData['coordinates']);
    $newsQuery = [
      'neighbourhoods' => array_values(array_map(static fn (Term $term) => $term->getTid(), $neighborhoods)),
    ];
    $newsArchiveUrl = $this->getInternalSearchLink(InternalSearchLink::NEWS_ARCHIVE, $newsQuery);

    $eventsNearYouRoute = Url::fromRoute('helfi_etusivu.helsinki_near_you_events', [], [
      'query' => [
        'address' => $addressName,
      ]
    ]);

    return [
      '#attached' => [
        'drupalSettings' => [
          'helfi_events' => [
            'baseUrl' => LinkedEvents::BASE_URL,
            'data' => [
              'helfi-coordinates-based-event-list' => [
                'events_api_url' => $this->linkedEvents->getEventsRequest([
                  'dwithin_origin' => implode(',', $addressData['coordinates']),
                  'dwithin_metres' => 2000,
                ]),
                'field_event_count' => 3,
                'hidePagination' => TRUE,
              ],
            ],
            'seeAllButtonOverride' => $this->t('See all events near you', [], ['context' => 'Helsinki near you']),
            'seeAllNearYouLink' => $eventsNearYouRoute->toString(),
            'useExperimentalGhosts' => TRUE,
          ],
          'helfi_news_archive' => [
            'elastic_proxy_url' => $this->config('elastic_proxy.settings')->get('elastic_proxy_url'),
            'default_query' => http_build_query($newsQuery),
            'hide_form' => TRUE,
            'max_results' => 3,
          ],
        ],
      ],
      '#back_link_label' => $this->t('Edit address', [], ['context' => 'Helsinki near you']),
      '#back_link_url' => $return_url,
      '#news_archive_url' => $newsArchiveUrl,
      '#cache' => [
        'contexts' => ['url.query_args:q'],
      ],
      '#coordinates' => $addressData['coordinates'],
      '#theme' => 'helsinki_near_you_results_page',
      '#title' => $this->t(
        'Services, events and news near your address @address',
        ['@address' => $addressName],
        ['context' => 'Helsinki near you']
      ),
      '#nearby_neighbourhoods' => $neighborhoods,
      '#service_groups' => $this->buildServiceGroups($addressName),
    ];
  }

  /**
   * Builds service groups render array.
   *
   * @param string $addressName
   *   Current address.
   *
   * @return array
   *   Render array.
   */
  public function buildServiceGroups(string $addressName) : array {
    $addressQuery = ['address' => $addressName];
    $viewsAddressQuery = ['address_search' => $addressName];

    return [
      [
        'title' => $this->t('Health is key', [], ['context' => 'Helsinki near you']),
        'service_links' => [
          [
            'link_label' => $this->t('Your own health station', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::HEALTH_STATIONS, $addressQuery),
          ],
          [
            'link_label' => $this->t('Closest maternity and child health clinic', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::CHILD_HEALTH_STATIONS, $addressQuery),
          ],
        ],
      ],
      [
        'title' => $this->t('Grow and learn', [], ['context' => 'Helsinki near you']),
        'service_links' => [
          [
            'link_label' => $this->t('Schools close to you', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::SCHOOLS, $addressQuery),
          ],
          [
            'link_label' => $this->t('Closest playgrounds and family houses', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::PLAYGROUNDS_FAMILY_HOUSES, $viewsAddressQuery),
          ],
          [
            'link_label' => $this->t('Closest daycare centres', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::DAYCARES, $viewsAddressQuery),
          ],
        ],
      ],
      [
        'title' => $this->t('Move around the city', [], ['context' => 'Helsinki near you']),
        'service_links' => [
          [
            'link_label' => $this->t('Roadway ploughing schedule', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::PLOWING_SCHEDULES, $addressQuery),
          ],
          [
            'link_label' => $this->t('Roadworks and events on map', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->servicemap->getLink(ServiceMapLink::ROADWORK_EVENTS, $addressName),
          ],
          [
            'link_label' => $this->t('City bike stations on map', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->servicemap->getLink(ServiceMapLink::CITYBIKE_STATIONS_STANDS, $addressName),
          ],
        ],
      ],
      [
        'title' => $this->t('The city is developing', [], ['context' => 'Helsinki near you']),
        'service_links' => [
          [
            'link_label' => $this->t('Street and park development on map', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->servicemap->getLink(ServiceMapLink::STREET_PARK_PROJECTS, $addressName),
          ],
          [
            'link_label' => $this->t('Plans in process on map', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->servicemap->getLink(ServiceMapLink::PLANS_IN_PROCESS, $addressName),
          ],
        ],
      ],
    ];
  }

  /**
   * Serves autocomplete suggestions for the search form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The result as JSON.
   */
  public function addressSuggestions(Request $request) : JsonResponse {
    $q = $request->query->get('q');
    $suggestions = [];

    $results = $this->servicemap->query($q, 10);

    foreach ($results as $result) {
      $name = $this->resolveTranslation($result->name);

      $suggestions[] = [
        'label' => $name,
        'value' => $name,
      ];
    }

    return new JsonResponse($suggestions);
  }

  /**
   * Resolves the translation string for given translation object.
   *
   * Returns the translation for the current language if it exists, otherwise
   * returns the Finnish translation.
   *
   * @param \stdClass $translations
   *   The translations object.
   *
   * @return string
   *   The translated string.
   */
  protected function resolveTranslation(\stdClass $translations) : string {
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    return $translations->{"$langcode"} ?? $translations->fi;
  }

  /**
   * Generate link to internal search with query params.
   *
   * @param \Drupal\helfi_etusivu\Enum\InternalSearchLink $link
   *   Internal search link option.
   * @param array $query
   *   Query params for the link.
   *
   * @return string
   *   The resulting link.
   */
  protected function getInternalSearchLink(
    InternalSearchLink $link,
    array $query,
  ) : string {
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    $url = Url::fromUri(
      $link->getLinkTranslations()[$langcode],
      ['query' => $query],
    );

    return $url->toString();
  }

  /**
   * Get nearby news neighbourhoods.
   *
   * @param array $coordinates
   *   Coordinates tuple.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Helfi: news Neighbourhoods entities.
   */
  protected function getNearbyNewsNeighbourhoods(array $coordinates): array {
    $storage = $this->entityTypeManager()
      ->getStorage('helfi_news_neighbourhoods');
    $query = $storage
      ->getQuery();

    assert($query instanceof Query);
    $query->setParameter('location', [
      $coordinates,
      [
        'unit' => 'km',
        'order' => 'asc',
        // 'arc' is more accurate, but within
        // a city it should not matter.
        'distance_type' => 'plane',
        // What to do in case a field has several geo points.
        'mode' => 'min',
        // Unmapped field cause the search to fail.
        'ignore_unmapped' => FALSE,
      ],
    ], 'GEO_DISTANCE_SORT');

    $ids = $query
      ->range(length: 3)
      ->condition('search_api_language', $this->languageManager()->getCurrentLanguage()->getId())
      ->accessCheck(FALSE)
      ->execute();

    return $storage->loadMultiple($ids);
  }

}
