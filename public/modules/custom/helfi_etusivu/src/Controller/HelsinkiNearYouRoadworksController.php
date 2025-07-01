<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\helfi_etusivu\ServiceMapInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

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
   */
  public function __construct(
    #[Autowire(service: 'Drupal\helfi_etusivu\Servicemap')]
    protected readonly ServiceMapInterface $servicemap,
    protected readonly RequestStack $requestStack,
  ) {
  }

  /**
   * Returns the roadworks listing page.
   *
   * @return array
   *   A renderable array.
   */
  public function content(): array {
    $language = $this->languageManager()->getCurrentLanguage()->getId();
    
    // Get address from query parameter
    $request = $this->requestStack->getCurrentRequest();
    $address = $request ? $request->query->get('q', '') : '';
    
    // Build API URL with coordinates if address is provided
    $apiUrl = '/' . $language . '/api/helsinki-near-you/roadworks';
    if (!empty($address)) {
      try {
        // Convert address to coordinates server-side
        $addressData = $this->servicemap->getAddressData(urldecode($address));
        
        if (!empty($addressData) && !empty($addressData['coordinates'])) {
          // Extract coordinates from GeoJSON format [longitude, latitude]
          $lat = $addressData['coordinates'][1]; // Latitude
          $lon = $addressData['coordinates'][0]; // Longitude
          
          // Add coordinates and original address to API URL
          $apiUrl .= '?lat=' . $lat . '&lon=' . $lon . '&q=' . urlencode($address);
        }
      } catch (\Exception $e) {
        // If address conversion fails, API URL will have no coordinates
        // React app will handle empty state
      }
    }
    
    return [
      '#attached' => [
        'drupalSettings' => [
          'helfi_roadworks' => [
            'data' => [
              'helfi-coordinates-based-roadwork-list' => [
                'roadworks_api_url' => $apiUrl,
                'field_roadwork_count' => 10,
                'field_roadwork_location' => TRUE,
                'field_roadwork_schedule' => TRUE,
                'hideHeading' => TRUE,
                'useLocationSearch' => TRUE,
                'hidePagination' => FALSE,
              ],
            ],
            'seeAllButtonOverride' => $this->t(
              'View all roadworks',
              [],
              ['context' => 'Helsinki near you roadworks search']
            ),
            'cardsWithBorders' => TRUE,
            'useExperimentalGhosts' => TRUE,
          ],
        ],
      ],
      '#theme' => 'helsinki_near_you_roadworks',
      '#title' => $this->t('Roadworks near you', [], ['context' => 'Helsinki near you']),
    ];
  }

}
