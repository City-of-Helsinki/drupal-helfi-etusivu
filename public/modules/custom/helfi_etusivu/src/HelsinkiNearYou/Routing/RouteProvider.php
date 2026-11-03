<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Routing;

use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\EventsController;
use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\FeedbackController;
use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\RoadworksController;
use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\VehicleRemovalController;
use Symfony\Component\Routing\Route;

/**
 * The route provider for 'Helsinki near you' pages.
 */
final class RouteProvider {

  /**
   * The route collection.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   The route collection.
   */
  public function routes(): array {
    $controllers = [
      'roadworks' => RoadworksController::class,
      'feedback' => FeedbackController::class,
      'events' => EventsController::class,
    ];

    // Simple htmx-controller, no search page.
    $htmxOnly = [
      'vehicle_removal' => VehicleRemovalController::class,
    ];

    $routes = [];
    foreach ($controllers as $name => $controller) {
      $route = sprintf('/helsinki-near-you/%s', $name);
      $routeName = sprintf('helfi_etusivu.helsinki_near_you_%s', $name);

      $routes[$routeName] = new Route(
        path: $route,
        defaults: [
          '_controller' => $controller . '::content',
          '_title_callback' => $controller . '::getTitle',
        ],
        requirements: [
          '_permission' => 'access content',
        ]
      );
      $routes[$routeName . '_htmx'] = new Route(
        path: $route . '/htmx',
        defaults: [
          '_controller' => $controller . '::htmx',
        ],
        requirements: [
          '_permission' => 'access content',
        ],
        options: [
          '_htmx_route' => 'TRUE',
        ]
      );
    }

    foreach ($htmxOnly as $name => $controller) {
      $routes[$this->getRouteName($name) . '_htmx'] = $this->createHtmxRoute($name, $controller);
    }

    return $routes;
  }

  /**
   * Gets the route name for the given page.
   *
   * @param string $name
   *   The page name.
   *
   * @return string
   *   The route name.
   */
  private function getRouteName(string $name): string {
    return sprintf('helfi_etusivu.helsinki_near_you_%s', $name);
  }

  /**
   * Get the path for the given page.
   *
   * @param string $name
   *   The page name.
   *
   * @return string
   *   The path.
   */
  private function getPath(string $name): string {
    return sprintf('/helsinki-near-you/%s', $name);
  }

  /**
   * Create the HTMX route for the given controller.
   *
   * @param string $name
   *   The page name.
   * @param class-string $controller
   *   The controller class.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route.
   */
  private function createHtmxRoute(string $name, string $controller): Route {
    return new Route(
      path: $this->getPath($name) . '/htmx',
      defaults: [
        '_controller' => $controller . '::htmx',
      ],
      requirements: [
        '_permission' => 'access content',
      ],
      options: [
        '_htmx_route' => 'TRUE',
      ]
    );
  }

}
