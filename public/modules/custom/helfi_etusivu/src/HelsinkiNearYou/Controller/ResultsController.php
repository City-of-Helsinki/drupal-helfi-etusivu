<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\external_entities\Entity\Query\External\Query;
use Drupal\helfi_etusivu\Enum\InternalSearchLink;
use Drupal\helfi_etusivu\Enum\ServiceMapLink;
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
class ResultsController extends ControllerBase {

  use FeedbackTrait;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\ServiceMapInterface $serviceMap
   *   The servicemap service.
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents $linkedEvents
   *   The linked events service.
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataServiceInterface $roadworkDataService
   *   The roadwork data service.
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\CoordinateConversionService $coordinateConversionService
   *   The coordinate conversion service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(
    protected readonly ServiceMapInterface $serviceMap,
    protected readonly LinkedEvents $linkedEvents,
    protected readonly RoadworkDataServiceInterface $roadworkDataService,
    protected readonly CoordinateConversionService $coordinateConversionService,
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
    $return_url = Url::fromRoute('helfi_etusivu.helsinki_near_you');

    if (!$address) {
      $this->messenger()->addError($this->t('Please enter an address', [], ['context' => 'Helsinki near you']));
      return $this->redirect('helfi_etusivu.helsinki_near_you');
    }
    $address = Xss::filter($address);
    $addressData = $this->serviceMap->getAddressData(urldecode($address));

    if (!$addressData) {
      $this->messenger()->addError(
        $this->t(
          'Make sure the address is written correctly. You can also search using a nearby street number.',
          [],
          ['context' => 'React search: Address not found hint']
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
      ],
    ]);

    // Extract coordinates for roadwork section.
    // Array format: [longitude, latitude] per GeoJSON specification.
    [$lon, $lat] = $addressData['coordinates'];

    $roadworkSection = $this->buildRoadworkSection($request, $lat, $lon, $address);

    $build = [
    // Set the theme for the results page.
      '#theme' => 'helsinki_near_you_results_page',
      '#attached' => [
        'drupalSettings' => [
          'helfi_events' => [
            'baseUrl' => LinkedEvents::BASE_URL,
            'cardsWithBorders' => TRUE,
            'data' => [
              'helfi-coordinates-based-event-list' => [
                'events_api_url' => $this->linkedEvents->getEventsRequest([
                  'dwithin_origin' => implode(',', $addressData['coordinates']),
                  'dwithin_metres' => 2000,
                ]),
                'field_event_count' => 3,
                'hidePagination' => TRUE,
                'removeBloatingEvents' => TRUE,
              ],
            ],
            'seeAllNearYouLink' => $eventsNearYouRoute->toString(),
          ],
          'helfi_news_archive' => [
            'elastic_proxy_url' => $this->config('elastic_proxy.settings')->get('elastic_proxy_url'),
            'default_query' => http_build_query($newsQuery),
            'hide_form' => TRUE,
            'max_results' => 3,
            'cardsWithBorders' => TRUE,
          ],
          'helfi_roadworks' => [
            'helfi-coordinates-based-roadwork-list' => [
              'cardsWithBorders' => TRUE,
              'initialData' => [
                'lat' => $lat,
                'lon' => $lon,
                'q' => $address,
              ],
              'isShortList' => TRUE,
              'roadworkCount' => 3,
              'scrollToTarget' => FALSE,
            ],
          ],
        ],
      ],
      '#back_link_label' => $this->t('Edit address', [], ['context' => 'Helsinki near you']),
      '#back_link_url' => $return_url,
      '#news_archive_url' => $newsArchiveUrl,
      '#coordinates' => $addressData['coordinates'],
      '#title' => $this->t(
        'Services, events and news for @address',
        ['@address' => $addressName],
        ['context' => 'Helsinki near you']
      ),
      '#nearby_neighbourhoods' => $neighborhoods,
      '#service_groups' => $this->buildServiceGroups($addressName),
      // Include roadwork section in the build array.
      '#roadwork_section' => $roadworkSection,
      '#cache' => [
        'contexts' => ['url.query_args:q'],
        'tags' => ['roadwork_section'],
      ],
      '#feedback_archive_url' => Url::fromRoute('helfi_etusivu.helsinki_near_you_feedbacks', options: [
        'query' => ['q' => $address],
      ]),
      '#feedback_section' => $this->buildFeedback($lon, $lat, 3, ['classes' => ['card--border']]),
    ];
    return $build;
  }

  /**
   * JSON API endpoint for roadworks data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with roadworks data.
   */
  public function roadworksApi(Request $request): JsonResponse {
    // Get coordinates and address from query parameters.
    $lat = (float) $request->query->get('lat', 0.0);
    $lon = (float) $request->query->get('lon', 0.0);
    $address = $request->query->get('q', '');

    if (!$lat || !$lon) {
      return new JsonResponse([
        'data' => [],
        'meta' => [
          'count' => 0,
          'error' => 'No coordinates provided',
        ],
      ], 400);
    }

    $roadworkData = $this->buildRoadworkSection($request, $lat, $lon, $address);

    return new JsonResponse([
      'data' => $roadworkData['projects'] ?? [],
      'meta' => [
        'count' => count($roadworkData['projects'] ?? []),
        'title' => $roadworkData['title'] ?? '',
        'see_all_url' => $roadworkData['see_all_url'] ?? '',
      ],
    ]);
  }

  /**
   * Builds the roadwork section.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param float $lat
   *   The latitude in WGS84.
   * @param float $lon
   *   The longitude in WGS84.
   * @param string $address
   *   The address string for 'See all' link.
   *
   * @return array
   *   The roadwork project data array.
   */
  public function buildRoadworkSection(Request $request, float $lat, float $lon, string $address = ''): array {
    try {
      // Convert WGS84 coordinates to ETRS-GK25 (EPSG:3879) projection
      // which is required by the roadwork data service.
      $convertedCoords = $this->coordinateConversionService->wgs84ToEtrsGk25($lat, $lon);

      if (!$convertedCoords) {
        throw new \RuntimeException('Failed to convert coordinates to ETRS-GK25');
      }

      // Fetch roadwork projects within 1km radius of the converted coordinates.
      // Note: Parameters are in ETRS-GK25 projection (EPSG:3879) where:
      // - First parameter is northing (y-coordinate)
      // - Second parameter is easting (x-coordinate)
      // - Third parameter is search radius in meters.
      $projects = $this->roadworkDataService->getFormattedProjectsByCoordinates(
      // Northing (y-coordinate)
        $convertedCoords['y'],
      // Easting (x-coordinate)
        $convertedCoords['x'],
      // 1000 meters = 1km radius.
        1000
      ) ?? [];

      foreach ($projects as &$project) {
        if (!isset($project['coordinates'])) {
          continue;
        }

        $convertedProjectCoords = $this->coordinateConversionService->etrsGk25ToWgs84(
          $project['coordinates'][0],
          $project['coordinates'][1],
        );

        if ($convertedProjectCoords) {
          $project['coordinates'] = [
            'lat' => $convertedProjectCoords['y'],
            'lon' => $convertedProjectCoords['x'],
          ];
        }
        else {
          unset($project['coordinates']);
        }
      }

      $title = $this->roadworkDataService->getSectionTitle();

      // Use provided address for 'See all' link, or get from request if not
      // provided.
      if (empty($address)) {
        $address = $request->query->get('q', '');
      }

      return [
        'title' => $title,
        'see_all_url' => $this->roadworkDataService->getSeeAllUrl($address)
          ->toString(),
        'projects' => $projects,
      ];

    }
    catch (\Exception) {
      // Return empty results structure on error to prevent page breakage
      // Use provided address for 'See all' link, or get from request if not
      // provided.
      if (empty($address)) {
        $address = $request->query->get('q', '');
      }

      return [
        'title' => $this->roadworkDataService->getSectionTitle(),
        'see_all_url' => $this->roadworkDataService->getSeeAllUrl($address)
          ->toString(),
        'projects' => [],
      ];
    }
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
            'link_label' => $this->t('Your health station', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::HEALTH_STATIONS, $addressQuery),
          ],
          [
            'link_label' => $this->t('The maternity and child health clinic near you', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::CHILD_HEALTH_STATIONS, $addressQuery),
          ],
        ],
      ],
      [
        'title' => $this->t('Grow and learn', [], ['context' => 'Helsinki near you']),
        'service_links' => [
          [
            'link_label' => $this->t('Your local school', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::SCHOOLS, $addressQuery),
          ],
          [
            'link_label' => $this->t('Playgrounds and family houses near you', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::PLAYGROUNDS_FAMILY_HOUSES, $viewsAddressQuery, 'views-exposed-form-playground-search-block'),
          ],
          [
            'link_label' => $this->t('Daycare centres near you', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::DAYCARES, $viewsAddressQuery, 'views-exposed-form-daycare-search-block'),
          ],
        ],
      ],
      [
        'title' => $this->t('Getting around the city', [], ['context' => 'Helsinki near you']),
        'service_links' => [
          [
            'link_label' => $this->t('Ploughing schedule', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->getInternalSearchLink(InternalSearchLink::PLOWING_SCHEDULES, $addressQuery),
          ],
          [
            'link_label' => $this->t('Roadworks on the map', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->serviceMap->getLink(ServiceMapLink::ROADWORK_EVENTS, $addressName),
          ],
          [
            'link_label' => $this->t('City bike stations and bikeracks on the map', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->serviceMap->getLink(ServiceMapLink::CITYBIKE_STATIONS_STANDS, $addressName),
          ],
        ],
      ],
      [
        'title' => $this->t('Urban development', [], ['context' => 'Helsinki near you']),
        'service_links' => [
          [
            'link_label' => $this->t('Street and park projects on the map', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->serviceMap->getLink(ServiceMapLink::STREET_PARK_PROJECTS, $addressName),
          ],
          [
            'link_label' => $this->t('Plans under preparation on the map', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->serviceMap->getLink(ServiceMapLink::PLANS_IN_PROCESS, $addressName),
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
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    return $translations->{"$langcode"} ?? $translations->fi;
  }

  /**
   * Generate link to internal search with query params.
   *
   * @param \Drupal\helfi_etusivu\Enum\InternalSearchLink $link
   *   Internal search link option.
   * @param array $query
   *   Query params for the link.
   * @param string|null $anchor
   *   Anchor to add to the link.
   *
   * @return string
   *   The resulting link.
   */
  protected function getInternalSearchLink(
    InternalSearchLink $link,
    array $query,
    ?string $anchor = NULL,
  ) : string {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $url = Url::fromUri(
      $link->getLinkTranslations()[$langcode],
      ['query' => $query],
    );

    return $url->toString() . ($anchor ? "#$anchor" : '');
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
      ->condition('search_api_language', $this->languageManager->getCurrentLanguage()->getId())
      ->accessCheck(FALSE)
      ->execute();

    return $storage->loadMultiple($ids);
  }

}
