<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\helfi_global_navigation\Entity\Form\GlobalMenuOverviewForm;
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

    $route = (new Route('/admin/content/integrations/global_menu'))
      ->addDefaults([
        '_form' => GlobalMenuOverviewForm::class,
        '_title' => 'Global menus',
      ])
      ->setOption('_admin_route', TRUE)
      ->setRequirement('_permission', $entity_type->getAdminPermission());
    $collection->add('entity.global_menu.collection', $route);

    return $collection;
  }

}
