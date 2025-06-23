<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\helfi_etusivu\RoadworkData\RoadworkDataServiceInterface;
use Drupal\helfi_etusivu\Servicemap;
use Drupal\helfi_etusivu\Service\CoordinateConversionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the Helsinki Near You Roadworks page.
 *
 * This controller handles the display of roadwork projects near a given location.
 * It provides methods for:
 * - Displaying roadwork projects near a user-specified address
 * - Handling address-based searches
 * - Formatting project data for display in the Helsinki Design System
 * - Managing error states and user feedback
 *
 * @see \Drupal\helfi_etusivu\RoadworkData\RoadworkDataServiceInterface
 * @see \Drupal\helfi_etusivu\RoadworkData\RoadworkDataClientInterface
 */
class HelsinkiNearYouRoadworksController extends ControllerBase {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_etusivu\RoadworkData\RoadworkDataServiceInterface $roadworkDataService
   *   The roadwork data service.
   * @param \Drupal\helfi_etusivu\Servicemap $servicemap
   *   The servicemap service.
   * @param \Drupal\helfi_etusivu\Service\CoordinateConversionService $coordinateConversionService
   *   The coordinate conversion service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected readonly RoadworkDataServiceInterface $roadworkDataService,
    protected readonly Servicemap $servicemap,
    protected readonly CoordinateConversionService $coordinateConversionService,
    protected readonly RequestStack $requestStack,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('helfi_etusivu.roadwork_data_service'),
      $container->get('helfi_etusivu.servicemap'),
      $container->get('Drupal\helfi_etusivu\Service\CoordinateConversionService'),
      $container->get('request_stack')
    );
  }

  /**
   * Builds the render array for the roadworks page.
   *
   * Constructs the complete render array for the roadworks page, including:
   * - Page title and description
   * - Project listings with pagination
   * - Address search form pre-filled with current search
   * - Appropriate cache metadata
   *
   * @param array $projects
   *   Array of roadwork project data, where each project contains:
   *   - title: (string) Project title/description
   *   - location: (string) Project address
   *   - schedule: (string) Formatted date range
   *   - url: (string) Link to more information
   *   - type: (string) Type of work
   *   - raw_data: (array) Original project data
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The page title to display.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   (optional) Description text to display below the title.
   *
   * @return array
   *   A render array with the following structure:
   *   - #theme: The theme hook to use
   *   - #title: Page title
   *   - #description: Page description
   *   - #roadworks_data: Array containing project data and pagination info
   *   - #cache: Cache metadata including URL query arguments
   *   - #attached: Required libraries and settings
   */
  protected function buildResponse(array $projects, $title, $description = ''): array {
    // Get the current request from the request stack.
    $request = $this->requestStack->getCurrentRequest();
    $address = $request->query->get('q', '');

    // Prepare the build array.
    $build = [
      '#theme' => 'helsinki_near_you_roadworks',
      '#title' => $title,
      '#description' => $description,
      '#address' => $address,
      '#roadworks_data' => [
        'title' => $title,
        'description' => $description,
        'projects' => $projects,
        'pagination' => [
          'total' => count($projects),
          'current_page' => 1,
          'per_page' => 10,
        ],
      ],
      '#cache' => [
        'contexts' => ['url.query_args:q'],
        'tags' => ['roadwork_section'],
      ],
    ];

    // Add the Helsinki Design System library.
    $build['#attached']['library'][] = 'hdbt/event-list';

    return $build;
  }

  /**
   * Displays roadworks near a given address or shows search instructions.
   *
   * Main controller method that handles:
   * - Extracting the search address from the request
   * - Validating the address
   * - Fetching and formatting roadwork projects
   * - Handling errors and edge cases
   * - Returning a properly structured render array
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request containing the 'q' parameter with the
   *   search address.
   *
   * @return array
   *   A render array for the roadworks page, including:
   *   - Project listings (if search was successful)
   *   - Search form with current query
   *   - Error messages (if any)
   *   - Help text for empty searches
   *
   * @see \Drupal\helfi_etusivu\RoadworkData\RoadworkDataServiceInterface
   */
  public function content(Request $request): array {
    // Get the address from the query parameters.
    $address = $request->query->get('q', '');

    // If no address is provided, show instructions.
    if (empty($address)) {
      return $this->buildResponse(
        [],
        $this->t('Roadworks in Helsinki'),
        $this->t('Search for roadwork projects in your area by providing an address.')
      );
    }

    try {
      // Get coordinates from servicemap.
      $addressData = $this->servicemap->getAddressData(urldecode($address));

      if (empty($addressData) || empty($addressData['coordinates'])) {
        throw new \Exception('Could not get coordinates for address');
      }

      // Get coordinates.
      // Latitude.
      $lat = $addressData['coordinates'][1];
      // Longitude.
      $lon = $addressData['coordinates'][0];

      // Convert coordinates to ETRS-GK25 (EPSG:3879)
      $convertedCoords = $this->coordinateConversionService->wgs84ToEtrsGk25($lat, $lon);

      if (!$convertedCoords) {
        throw new \Exception('Failed to convert coordinates to ETRS-GK25');
      }

      // Get roadwork data using the converted coordinates.
      $projects = $this->roadworkDataService->getFormattedProjectsByCoordinates(
      // Lat in ETRS-GK25 (northing)
        $convertedCoords['y'],
      // Lon in ETRS-GK25 (easting)
        $convertedCoords['x'],
      // 1km radius.
        1000
      );

      // Build the response with the roadwork data.
      return $this->buildResponse(
        $projects,
        $this->t('Katu- ja puistohankkeet lähelläsi'),
        ''
      );

    }
    catch (\Exception $e) {
      // Return error message to the user.
      return $this->buildResponse(
            [],
            $this->t('Error loading roadwork data'),
            $this->t('We encountered an error while loading roadwork data. Please try again later.')
          );
    }

    return [
      '#theme' => 'helsinki_near_you_roadworks',
      '#title' => $this->t('Katu- ja puistohankkeet lähelläsi'),
      '#roadworks_data' => $projectsData,
      '#address' => $address,
      '#cache' => [
        'contexts' => ['url.query_args:q'],
        'tags' => ['roadwork_section'],
      ],
    ];
  }

}
