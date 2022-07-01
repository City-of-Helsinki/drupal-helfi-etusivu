<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\helfi_global_navigation\Service\GlobalNavigationService;

/**
 * Synchronizes global menu.
 */
class MenuUpdater {
  /**
   * Main menu machine name.
   */
  protected const MAIN_MENU = 'main';

  /**
   * Max depth for menu item synchronization.
   */
  protected const MAX_DEPTH = 2;

  /**
   * Current environment.
   *
   * @var string
   */
  protected $env;

  /**
   * Constructs MenuUpdater.
   */
  public function __construct(
    protected ConfigFactory $config,
    protected GlobalNavigationService $globalNavigationService
  ) {}

  /**
   * Sends main menu tree to frontpage instance.
   *
   * @param string $langcode
   *   Language for the menu.
   */
  public function syncMenu($langcode): void {
    if ($this->globalNavigationService->inFrontPage()) {
      return;
    }

    $currentProject = $this->globalNavigationService->getCurrentProject();
    $siteName = $this->config->get('system.site')->get('name');

    $options = [
      'json' => [
        'name' => $siteName,
        'menu_tree' => (object) [
          'name' => $siteName,
          'url' => $currentProject['url'],
          'id' => $currentProject['id'],
          'menu_tree' => $this->buildMenuTree(),
        ],
      ],
    ];

    $endpoint = '/global-menus/' . $currentProject['id'];
    $this->globalNavigationService->makeRequest(Project::ETUSIVU, 'POST', $endpoint, $options);
  }

  /**
   * Builds menu tree for synchronization.
   *
   * @return array
   *   The resulting tree.
   */
  protected function buildMenuTree(): array {
    $drupalTree = \Drupal::menuTree()->load(self::MAIN_MENU, new MenuTreeParameters());

    return $this->transformMenuItems($drupalTree);
  }

  /**
   * Transform menu items to response format.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $menuItems
   *   Array of menu items.
   */
  protected function transformMenuItems(array $menuItems): array {
    $transformedItems = [];

    foreach ($menuItems as $menuItem) {
      $menuLink = $menuItem->link;
      $subTree = $menuItem->subtree;

      $transformedItem = [
        'id' => $menuLink->getPluginId(),
        'name' => $menuLink->getTitle(),
        'url' => $menuLink->getUrlObject()->setAbsolute(TRUE)->toString(),
      ];

      if (count($subTree) > 0 && $menuItem->depth < self::MAX_DEPTH) {
        $transformedItem['sub_tree'] = $this->transformMenuItems($subTree);
      }

      $transformedItems[] = (object) $transformedItem;
    }

    return $transformedItems;
  }

}
