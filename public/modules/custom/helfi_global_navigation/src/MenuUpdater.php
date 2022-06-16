<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_api_base\Environment\EnvironmentResolver;
use Drupal\helfi_api_base\Environment\Project;
use GuzzleHttp\ClientInterface;
use GuzzlleHttp\json_encode;

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
    protected EnvironmentResolver $environmentResolver,
    protected ClientInterface $httpClient,
    protected LanguageManagerInterface $languageManager
  ) {
    $this->env = getenv('APP_ENV');
  }

  /**
   * Sends main menu tree to frontpage instance.
   */
  public function syncMenu(): void {
    $currentProject = $this->getCurrentEnvironment();

    // Bail if current environment isn't listed in resolver.
    if (!$currentProject) {
      return;
    }

    $frontpage = $this->environmentResolver->getEnvironment(Project::ETUSIVU, $this->env);

    // Return early if current instance is frontpage.
    if ($currenProject[$this->env]->getDomain() === $environment->getDomain()) {
      return;
    }

    $baseUrl = $frontpage->getUrl($this->languageManager->getCurrentLanguage()->getId());
    $menuTree = $this->buildMenuTree();

    $url = $baseUrl . '/global-menus/' . $currentProject['id'];
    try {
      $options = [
        'body' => json_encode([
          'name' => $this->config->get('system.site')->get('name'),
          'menu_tree' => $menuTree,
        ]),
      ];

      // Disable SSL verify in local environment.
      if ($this->env === 'local') {
        $options['verify'] = FALSE;
      }

      $this->httpClient->request('POST', $url, $options);
    }
    catch (\Exception $e) {

    }
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
        'name' => $menuLink->getTitle(),
        'url' => $menuLink->getUrlObject()->setAbsolute(TRUE)->toString(),
      ];

      if (count($subTree) > 0 && $menuItem->depth < self::MAX_DEPTH) {
        $transformedItem['sub_tree'] = $this->transformMenuItems($subTree);
      }

      $transformedItems[] = $transformedItem;
    }

    return $transformedItems;
  }

  /**
   * Determine current project.
   *
   * @return array|null
   *   The resulting environment or null.
   */
  protected function getCurrentEnvironment(): array|NULL {
    $projects = $this->environmentResolver->getProjects();
    $currentHost = \Drupal::request()->getHost();
    foreach ($projects as $key => $project) {
      if ($currentHost === $project[$this->env]->getDomain()) {
        return [
          'id' => $key,
          'project' => $project,
        ];
      }
    }

    return NULL;
  }

}
