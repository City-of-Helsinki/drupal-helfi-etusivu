<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\helfi_global_navigation\ExternalMenuTree;
use Drupal\helfi_global_navigation\ExternalMenuTreeFactory;
use Drupal\helfi_global_navigation\Service\GlobalNavigationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for creating external menu blocks.
 */
abstract class ExternalMenuBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs an instance of ExternalMenuBlockBase.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\helfi_global_navigation\ExternalMenuTreeFactory $menuTreeFactory
   *   Factory class for creating an instance of ExternalMenuTree.
   * @param \Drupal\helfi_global_navigation\Service\GlobalNavigationService $globalNavigationService
   *   Global navigation service.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, protected ExternalMenuTreeFactory $menuTreeFactory, protected GlobalNavigationService $globalNavigationService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('helfi_global_navigation.external_menu_tree_factory'),
      $container->get('helfi_global_navigation.global_navigation_service')
    );
  }

  /**
   * Build a renderable array from data.
   *
   * @return array|null
   *   The render array.
   */
  public function build():? array {
    $menuTree = $this->buildFromJson($this->getData());

    if (!$menuTree) {
      return NULL;
    }

    $items = $menuTree->getTree();

    $build = [];

    if ($items) {
      $build['#sorted'] = TRUE;
      $build['#theme'] = 'menu__external_menu';
      $build['#items'] = $items;

      // Set cache tag.
      // @todo set actual tags
      $build['#cache']['tags'][] = 'external-menu:' . $this->getPluginId();
    }

    return $build;
  }

  /**
   * Build menu from JSON.
   *
   * @param string $json
   *   JSON string to generate menu tree from.
   *
   * @return \Drupal\helfi_global_navigation\ExternalMenuTree
   *   The resulting menu tree.
   */
  protected function buildFromJson(string $json):? ExternalMenuTree {
    try {
      $menuTree = $this->menuTreeFactory->fromJson($json, $this->maxDepth());
      return $menuTree;
    }
    catch (\throwable $e) {
      return NULL;
    }
  }

}
