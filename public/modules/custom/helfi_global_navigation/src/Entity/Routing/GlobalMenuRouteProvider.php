<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for content entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
final class GlobalMenuRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) : RouteCollection {
    /** @var \Symfony\Component\Routing\RouteCollection $collection */
    $collection = parent::getRoutes($entity_type);

    $route = (new Route('/admin/content/integrations/global_menu/add'))
      ->addDefaults([
        '_entity_form' => 'global_menu.default',
        '_title' => 'Add menu',
      ])
      ->setRequirement('_access', 'TRUE');
    $collection->add('global_menu.add_form', $route);

    return $collection;
  }

}
