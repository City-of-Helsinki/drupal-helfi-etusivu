<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\helfi_global_navigation\Entity\GlobalMenu as GlobalMenuEntity;
use Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A base class for global menu resources.
 */
abstract class GlobalMenuResourceBase extends MenuResourceBase {

  /**
   * The entity storage.
   *
   * @var \Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage
   */
  protected GlobalMenuStorage $storage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->storage = $container->get('entity_type.manager')->getStorage('global_menu');

    return $instance;
  }

  /**
   * Constructs a new cacheable metadata object with default values.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   The cacheable metadata.
   */
  protected function getCacheableMetaData(Request $request) : CacheableMetadata {
    return (new CacheableMetadata())
      ->addCacheableDependency($request->attributes->get(AccessAwareRouterInterface::ACCESS_RESULT))
      ->addCacheContexts(['url.query_args']);
  }

  /**
   * Processes the given entity against request filters.
   *
   * @param \Drupal\helfi_global_navigation\Entity\GlobalMenu $entity
   *   The entity to process.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\helfi_global_navigation\Entity\GlobalMenu
   *   The processed entity.
   */
  protected function processEntityFilters(GlobalMenuEntity $entity, Request $request) : GlobalMenuEntity {
    $menuTree = $entity->getMenuTree();

    if ($menuTree && $maxDepth = $request->query->get('max-depth')) {
      $this->parseMaxDepth((int) $maxDepth, $menuTree);
      // Override the default menu tree after filters.
      $entity->setMenuTree($menuTree);
    }
    return $entity;
  }

  /**
   * Filters menu tree to given maximum depth.
   *
   * @param int $maxDepth
   *   The max depth.
   * @param object $menuTree
   *   The menu tree to parse.
   * @param int $currentDepth
   *   The current depth.
   *
   * @return object
   *   The parsed menu tree.
   */
  protected function parseMaxDepth(int $maxDepth, object $menuTree, int $currentDepth = 0) : object {
    $currentDepth = $currentDepth + 1;

    if (!isset($menuTree->sub_tree)) {
      $menuTree->sub_tree = [];
    }
    foreach ($menuTree->sub_tree as $delta => $tree) {
      $menuTree->sub_tree[$delta] = $this->parseMaxDepth($maxDepth, $tree, $currentDepth);

      if ($currentDepth >= $maxDepth && isset($menuTree)) {
        unset($menuTree->sub_tree[$delta]);
      }
    }
    return $menuTree;
  }

}
