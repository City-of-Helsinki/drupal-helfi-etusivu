<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Routing;

use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\EventsController;
use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\FeedbackController;
use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\RoadworksController;
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
    return $routes;
  }

}
