<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\external_entities\Entity\Query\External\Query;
use Drupal\helfi_etusivu\HelsinkiNearYou\Enum\InternalSearchLink;
use Drupal\helfi_etusivu\HelsinkiNearYou\Enum\ServiceMapLink;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Location;
use Drupal\helfi_etusivu\HelsinkiNearYou\ServiceMapInterface;
use Drupal\helfi_paragraphs_news_list\Entity\ExternalEntity\Term;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents;
use Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataServiceInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\CoordinateConversionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Helsinki near you controller.
 */
final class ResultsController extends ControllerBase {

  use LazyBuilderTrait;

  public function __construct(
    private readonly ServiceMapInterface $serviceMap,
    private readonly LinkedEvents $linkedEvents,
    private readonly RoadworkDataServiceInterface $roadworkDataService,
    private readonly CoordinateConversionService $coordinateConversionService,
    LanguageManagerInterface $languageManager,
  ) {
    $this->languageManager = $languageManager;
  }

  /**
   * Returns a renderable array.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A renderable array or redirect response.
   */
  public function content(Request $request): array|RedirectResponse {
    $address = $request->query->get('q');

    if (!$address) {
      $this->messenger()->addError($this->t('Please enter an address', [], ['context' => 'Helsinki near you']));
      return $this->redirect('helfi_etusivu.helsinki_near_you');
    }
    $address = $this->serviceMap->getAddressData(urldecode($address));

    if (!$address) {
      $this->messenger()->addError(
        $this->t(
          'Make sure the address is written correctly. You can also search using a nearby street number.',
          [],
          ['context' => 'React search: Address not found hint']
        )
      );
      return $this->redirect('helfi_etusivu.helsinki_near_you');
    }
    $langcode = $this->languageManager
      ->getCurrentLanguage()
      ->getId();
    $addressName = $address->streetName->getName($langcode);

    $neighborhoods = $this->getNearbyNewsNeighbourhoods($address->location, $langcode);
    $newsQuery = [
      'neighbourhoods' => array_values(array_map(static fn (Term $term) => $term->getTid(), $neighborhoods)),
    ];

    return [
      '#theme' => 'helsinki_near_you_results_page',
      '#attached' => [
        'drupalSettings' => [
          'helfi_events' => [
            'baseUrl' => LinkedEvents::BASE_URL,
            'cardsWithBorders' => TRUE,
            'data' => [
              'helfi-coordinates-based-event-list' => [
                'events_api_url' => $this->linkedEvents->getEventsRequest([
                  'dwithin_origin' => sprintf('%f,%f', $address->location->lon, $address->location->lat),
                  'dwithin_metres' => 2000,
                ]),
                'field_event_count' => 3,
                'hidePagination' => TRUE,
                'removeBloatingEvents' => TRUE,
              ],
            ],
            'seeAllNearYouLink' => Url::fromRoute('helfi_etusivu.helsinki_near_you_events', [], [
              'query' => [
                'address' => $addressName,
              ],
            ])->toString(),
          ],
          'helfi_news_archive' => [
            'elastic_proxy_url' => $this->config('elastic_proxy.settings')->get('elastic_proxy_url'),
            'default_query' => http_build_query($newsQuery),
            'hide_form' => TRUE,
            'max_results' => 3,
            'cardsWithBorders' => TRUE,
          ],
        ],
      ],
      '#back_link_label' => $this->t('Edit address', [], ['context' => 'Helsinki near you']),
      '#back_link_url' => Url::fromRoute('helfi_etusivu.helsinki_near_you'),
      '#news_archive_url' => $this->getInternalSearchLink(InternalSearchLink::NEWS_ARCHIVE, $newsQuery, $langcode),
      '#coordinates' => $address->location,
      '#title' => $this->t(
        'Services, events and news for @address',
        ['@address' => $addressName],
        ['context' => 'Helsinki near you']
      ),
      '#nearby_neighbourhoods' => $neighborhoods,
      '#service_groups' => $this->buildServiceGroups($addressName, $langcode),
      '#roadwork_archive_url' => $this->roadworkDataService->getSeeAllUrl($address, $langcode),
      '#roadwork_section' => $this->buildRoadworks($address, $langcode, 3, ['classes' => ['card--border']]),
      '#cache' => [
        'contexts' => ['url.query_args:q'],
        'tags' => ['roadwork_section'],
      ],
      '#feedback_archive_url' => Url::fromRoute('helfi_etusivu.helsinki_near_you_feedbacks', options: [
        'query' => ['q' => $addressName],
      ]),
      '#feedback_section' => $this->buildFeedback($address, $langcode, 3, ['classes' => ['card--border']]),
    ];
  }

  /**
   * Builds service groups render array.
   *
   * @param string $addressName
   *   Current address.
   * @param string $langcode
   *   The langcode.
   *
   * @return array
   *   Render array.
   */
  public function buildServiceGroups(string $addressName, string $langcode) : array {
    $addressQuery = ['address' => $addressName];
    $viewsAddressQuery = ['address_search' => $addressName];

    return [
      [
        'title' => $this->t('Health is key', [], ['context' => 'Helsinki near you']),
        'service_links' => [
          [
            'link_label' => $this->t('Your health station', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::HEALTH_STATIONS, $addressQuery, $langcode),
          ],
          [
            'link_label' => $this->t('The maternity and child health clinic near you', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::CHILD_HEALTH_STATIONS, $addressQuery, $langcode),
          ],
        ],
      ],
      [
        'title' => $this->t('Grow and learn', [], ['context' => 'Helsinki near you']),
        'service_links' => [
          [
            'link_label' => $this->t('Your local school', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::SCHOOLS, $addressQuery, $langcode),
          ],
          [
            'link_label' => $this->t('Playgrounds and family houses near you', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::PLAYGROUNDS_FAMILY_HOUSES, $viewsAddressQuery, $langcode, 'views-exposed-form-playground-search-block'),
          ],
          [
            'link_label' => $this->t('Daycare centres near you', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::DAYCARES, $viewsAddressQuery, $langcode, 'views-exposed-form-daycare-search-block'),
          ],
        ],
      ],
      [
        'title' => $this->t('Getting around the city', [], ['context' => 'Helsinki near you']),
        'service_links' => [
          [
            'link_label' => $this->t('Ploughing schedule', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::PLOWING_SCHEDULES, $addressQuery, $langcode),
          ],
          [
            'link_label' => $this->t('Roadworks on the map', [], ['context' => 'Helsinki near you']),
            'link_url' => ServiceMapLink::ROADWORK_EVENTS->getLink($addressName, $langcode),
          ],
          [
            'link_label' => $this->t('City bike stations and bikeracks on the map', [], ['context' => 'Helsinki near you']),
            'link_url' => ServiceMapLink::CITYBIKE_STATIONS_STANDS->getLink($addressName, $langcode),
          ],
        ],
      ],
      [
        'title' => $this->t('Urban development', [], ['context' => 'Helsinki near you']),
        'service_links' => [
          [
            'link_label' => $this->t('Street and park projects on the map', [], ['context' => 'Helsinki near you']),
            'link_url' => ServiceMapLink::STREET_PARK_PROJECTS->getLink($addressName, $langcode),
          ],
          [
            'link_label' => $this->t('Plans under preparation on the map', [], ['context' => 'Helsinki near you']),
            'link_url' => ServiceMapLink::PLANS_IN_PROCESS->getLink($addressName, $langcode),
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

    $results = $this->serviceMap->query($q, 10);

    foreach ($results as $result) {
      $name = $result->streetName->getName($this->languageManager->getCurrentLanguage()->getId());

      $suggestions[] = [
        'label' => $name,
        'value' => $name,
      ];
    }

    return new JsonResponse($suggestions);
  }

  /**
   * Generate link to internal search with query params.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Enum\InternalSearchLink $link
   *   Internal search link option.
   * @param array $query
   *   Query params for the link.
   * @param string $langcode
   *   The langcode.
   * @param string|null $anchor
   *   Anchor to add to the link.
   *
   * @return string
   *   The resulting link.
   */
  protected function getInternalSearchLink(
    InternalSearchLink $link,
    array $query,
    string $langcode,
    ?string $anchor = NULL,
  ) : string {

    $url = Url::fromUri(
      $link->getLinkTranslation($langcode),
      ['query' => $query],
    );

    return $url->toString() . ($anchor ? "#$anchor" : '');
  }

  /**
   * Get nearby news neighbourhoods.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Location $location
   *   The location.
   * @param string $langcode
   *   The language.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Helfi: news Neighbourhoods entities.
   */
  protected function getNearbyNewsNeighbourhoods(Location $location, string $langcode): array {
    $storage = $this->entityTypeManager()
      ->getStorage('helfi_news_neighbourhoods');
    $query = $storage
      ->getQuery();

    assert($query instanceof Query);
    $query->setParameter('location', [
      [$location->lat, $location->lon],
      [
        'unit' => 'km',
        'order' => 'asc',
        // 'arc' is more accurate but for city-scale distances,
        // the performance benefit of 'plane' is preferred.
        'distance_type' => 'plane',
        // What to do in case a field has several geo points.
        'mode' => 'min',
        // Unmapped field cause the search to fail.
        'ignore_unmapped' => FALSE,
      ],
    ], 'GEO_DISTANCE_SORT');

    $ids = $query
      ->range(length: 3)
      ->condition('search_api_language', $langcode)
      ->accessCheck(FALSE)
      ->execute();

    return $storage->loadMultiple($ids);
  }

}
