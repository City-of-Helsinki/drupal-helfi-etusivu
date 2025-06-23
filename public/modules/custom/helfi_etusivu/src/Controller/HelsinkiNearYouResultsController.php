<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\external_entities\Entity\Query\External\Query;
use Drupal\helfi_etusivu\Enum\InternalSearchLink;
use Drupal\helfi_etusivu\Enum\ServiceMapLink;
use Drupal\helfi_etusivu\ServiceMapInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\helfi_paragraphs_news_list\Entity\ExternalEntity\Term;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents;
use Drupal\helfi_etusivu\RoadworkData\RoadworkDataServiceInterface;
use Drupal\helfi_etusivu\Service\CoordinateConversionService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Helsinki near you controller.
 */
class HelsinkiNearYouResultsController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * The servicemap service.
   *
   * @var \Drupal\helfi_etusivu\ServiceMapInterface
   */
  protected $servicemap;

  /**
   * The linked events service.
   *
   * @var \Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents
   */
  protected $linkedEvents;

  /**
   * The roadwork data service.
   *
   * @var \Drupal\helfi_etusivu\RoadworkData\RoadworkDataServiceInterface
   */
  protected $roadworkDataService;

  /**
   * The coordinate conversion service.
   *
   * @var \Drupal\helfi_etusivu\Service\CoordinateConversionService
   */
  protected $coordinateConversionService;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_etusivu\ServiceMapInterface $servicemap
   *   The servicemap service.
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents $linkedEvents
   *   The linked events service.
   * @param \Drupal\helfi_etusivu\RoadworkData\RoadworkDataServiceInterface $roadworkDataService
   *   The roadwork data service.
   * @param \Drupal\helfi_etusivu\Service\CoordinateConversionService $coordinateConversionService
   *   The coordinate conversion service.
   */
  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_etusivu\ServiceMapInterface $servicemap
   *   The servicemap service.
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents $linkedEvents
   *   The linked events service.
   * @param \Drupal\helfi_etusivu\RoadworkData\RoadworkDataServiceInterface $roadworkDataService
   *   The roadwork data service.
   * @param \Drupal\helfi_etusivu\Service\CoordinateConversionService $coordinateConversionService
   *   The coordinate conversion service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    ServiceMapInterface $servicemap,
    LinkedEvents $linkedEvents,
    RoadworkDataServiceInterface $roadworkDataService,
    CoordinateConversionService $coordinateConversionService,
    RequestStack $requestStack
  ) {
    $this->servicemap = $servicemap;
    $this->linkedEvents = $linkedEvents;
    $this->roadworkDataService = $roadworkDataService;
    $this->coordinateConversionService = $coordinateConversionService;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('Drupal\helfi_etusivu\Servicemap'),
      $container->get('helfi_etusivu.helsinki_near_you.linked_events'),
      $container->get('helfi_etusivu.roadwork_data_service'),
      $container->get('Drupal\helfi_etusivu\Service\CoordinateConversionService'),
      $container->get('request_stack')
    );
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
  public function content(Request $request = NULL): array|RedirectResponse {
    // Use the provided request or fall back to the current request.
    $request = $request ?: $this->requestStack->getCurrentRequest();
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
          'Make sure the address is written correctly. You can also search using a nearby street number.',
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
      ],
    ]);

    // Extract coordinates for roadwork section.
    // Array format: [longitude, latitude] per GeoJSON specification.
    $lat = $addressData['coordinates'][1]; // Latitude at index 1
    $lon = $addressData['coordinates'][0]; // Longitude at index 0

    $roadworkSection = $this->buildRoadworkSection($lat, $lon);

    $build = [
    // Set the theme for the results page.
      '#theme' => 'helsinki_near_you_results_page',
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
            'seeAllNearYouLink' => $eventsNearYouRoute->toString(),
            'cardsWithBorders' => TRUE,
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
      '#back_link_label' => $this->t('Back to search'),
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
    ];

    return $build;
  }

  /**
   * Lazy builder for the roadwork section.
   *
   * @param float $lat
   *   The latitude.
   * @param float $lon
   *   The longitude.
   *
   * @return array
   *   A render array for the roadwork section.
   */
  public static function lazyBuildRoadworkSection(float $lat, float $lon): array {
    try {
      /** @var \Drupal\helfi_etusivu\Controller\HelsinkiNearYouResultsController $controller */
      $controller = \Drupal::getContainer()->get('helfi_etusivu.helsinki_near_you_results_controller');
      return $controller->buildRoadworkSection($lat, $lon);
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Builds the roadwork section.
   *
   * @param float $lat
   *   The latitude in WGS84.
   * @param float $lon
   *   The longitude in WGS84.
   *
   * @return array
   *   The roadwork project data array.
   */
  public function buildRoadworkSection(float $lat, float $lon): array {
    try {
      // Validate coordinate types to ensure they are floats.
      if (!is_float($lat) || !is_float($lon)) {
        throw new \InvalidArgumentException('Latitude and longitude must be float values');
      }

      if (!$this->roadworkDataService) {
        throw new \RuntimeException('Roadwork data service is not available');
      }

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
      // - Third parameter is search radius in meters
      $projects = $this->roadworkDataService->getFormattedProjectsByCoordinates(
        $convertedCoords['y'], // Northing (y-coordinate)
        $convertedCoords['x'], // Easting (x-coordinate)
        1000 // 1000 meters = 1km radius
      ) ?? [];

      $projectCount = count($projects);

      $title = $this->roadworkDataService->getSectionTitle();

      // Get the search address from the current request for the 'See all' link
      $request = $this->requestStack->getCurrentRequest();
      $address = $request ? $request->query->get('q', '') : '';
      $seeAllUrl = $this->roadworkDataService->getSeeAllUrl($address);

      return [
        'title' => $title,
        'see_all_url' => $seeAllUrl,
        'projects' => $projects,
      ];

    }
    catch (\Exception $e) {
      // Return empty results structure on error to prevent page breakage
      return [
        'title' => $this->roadworkDataService->getSectionTitle(),
        'see_all_url' => $this->roadworkDataService->getSeeAllUrl(),
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
            'link_url' => $this->servicemap->getLink(ServiceMapLink::ROADWORK_EVENTS, $addressName),
          ],
          [
            'link_label' => $this->t('City bike stations and bikeracks on the map', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->servicemap->getLink(ServiceMapLink::CITYBIKE_STATIONS_STANDS, $addressName),
          ],
        ],
      ],
      [
        'title' => $this->t('Urban development', [], ['context' => 'Helsinki near you']),
        'service_links' => [
          [
            'link_label' => $this->t('Street and park projects on the map', [], ['context' => 'Helsinki near you']),
            'link_url' => $this->servicemap->getLink(ServiceMapLink::STREET_PARK_PROJECTS, $addressName),
          ],
          [
            'link_label' => $this->t('Plans under preparation on the map', [], ['context' => 'Helsinki near you']),
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
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
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
      ->condition('search_api_language', $this->languageManager()->getCurrentLanguage()->getId())
      ->accessCheck(FALSE)
      ->execute();

    return $storage->loadMultiple($ids);
  }

}
