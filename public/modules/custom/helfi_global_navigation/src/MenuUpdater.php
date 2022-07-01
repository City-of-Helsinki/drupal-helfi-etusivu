<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\helfi_global_navigation\Service\GlobalNavigationService;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;

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
   * Langcode to filter menu items by.
   *
   * @var string
   */
  protected string $langcode;

  /**
   * Constructs MenuUpdater.
   */
  public function __construct(
    protected ConfigFactory $config,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected GlobalNavigationService $globalNavigationService
  ) {}

  /**
   * Sends main menu tree to frontpage instance.
   */
  public function syncMenu(): void {
    if ($this->globalNavigationService->inFrontPage()) {
      return;
    }

    if (!$this->langcode) {
      throw new \Exception('No langcode set for the menu updater.');
    }

    $currentProject = $this->globalNavigationService->getCurrentProject();
    $siteName = $this->config->get('system.site')->get('name');

    $options = [
      'json' => [
        'name' => $siteName,
        'langcode' => $this->langcode,
        'menu_tree' => (object) [
          'name' => $siteName,
          'url' => $currentProject['url'],
          'id' => $currentProject['id'],
          'menu_tree' => $this->buildMenuTree($langcode),
        ],
      ],
    ];

    $endpoint = '/global-menus/' . $currentProject['id'];
    $this->globalNavigationService->makeRequest(Project::ETUSIVU, 'POST', $endpoint, $options);
  }

  /**
   * Set the langcode.
   *
   * @param string $langcode
   *   The langcode.
   */
  public function setLangcode(string $langcode) {
    $this->langcode = $langcode;
  }

  /**
   * Builds menu tree for synchronization.
   *
   * @return array
   *   The resulting tree.
   */
  protected function buildMenuTree(): array {
    // @todo figure out if we can load the menu tree based on langcode.
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

      if (!$entity = $this->getEntity($menuLink)) {
        continue;
      }

      $translatable = $entity->isTranslatable();

      if ($translatable && !$entity->hasTranslation($this->langcode)) {
        continue;
      }

      if ($translatable) {
        $menuLink = $entity->getTranslation($this->langcode);
      }

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

  /**
   * Load entity with given menu link.
   *
   * @param \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $link
   *   The menu link.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|null
   *   Boolean if menu link has no metadata. NULL if entity not found and
   *   an EntityInterface if found.
   */
  protected function getEntity(MenuLinkContent $link) {
    // MenuLinkContent::getEntity() has protected visibility and cannot be used
    // to directly fetch the entity.
    $metadata = $link->getMetaData();

    if (empty($metadata['entity_id'])) {
      return FALSE;
    }
    return $this->entityTypeManager
      ->getStorage('menu_link_content')
      ->load($metadata['entity_id']);
  }

}
