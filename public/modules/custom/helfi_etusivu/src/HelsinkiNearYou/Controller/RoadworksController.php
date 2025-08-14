<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\LazyBuilder;
use Drupal\helfi_etusivu\HelsinkiNearYou\ServiceMapInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the Helsinki Near You Roadworks page.
 *
 * This controller handles the display of roadwork projects near a given
 * location.
 * It provides methods for:
 * - Displaying roadwork projects near a user-specified address
 * - Handling address-based searches
 * - Formatting project data for display in the Helsinki Design System
 * - Managing error states and user feedback.
 *
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataServiceInterface
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataClientInterface
 */
final class RoadworksController extends ControllerBase {

  use AutowireTrait;

  /**
   * Constructs a new instance.
   */
  public function __construct(
    private readonly ServiceMapInterface $serviceMap,
    private readonly LazyBuilder $lazyBuilder,
  ) {
  }

  /**
   * Returns the roadworks listing page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   A renderable array.
   */
  public function content(Request $request): array {
    $address = $request->query->get('q', '');

    $build = [
      'roadworkCount' => 10,
      'hidePagination' => FALSE,
      'cardsWithBorders' => FALSE,
      'scrollToTarget' => TRUE,
    ];

    if (!empty($address)) {
      try {
        $address = $this->serviceMap->getAddressData($address);

        if (!empty($addressData) && !empty($addressData['coordinates'])) {
          // Extract coordinates from GeoJSON format [longitude, latitude].
          [$lon, $lat] = $addressData['coordinates'];

          $build['initialData'] = [
            'lat' => $lat,
            'lon' => $lon,
            'q' => $address,
          ];
        }
      }
      catch (\Exception $e) {
        // If address conversion fails, API URL will have no coordinates
        // React app will handle empty state.
      }
    }

    return [
      '#attached' => [
        'drupalSettings' => [
          'helfi_roadworks' => [
            'helfi-coordinates-based-roadwork-list' => $build,
          ],
        ],
      ],
      '#theme' => 'helsinki_near_you_roadworks',
    ];
  }

}
