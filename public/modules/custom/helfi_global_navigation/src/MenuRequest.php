<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation;

/**
 * Wrapper class for JSON request.
 */
class MenuRequest {

  /**
   * Menu tree as array.
   *
   * @var array
   */
  protected array $menuTree;

  /**
   * Site name as array.
   *
   * @var array
   */
  protected array $siteName;

  /**
   * Key for the project.
   *
   * @var string
   */
  protected string $projectName;

  /**
   * Constructor for MenuRequest object.
   *
   * @param string $project_name
   *   Project name. Eg. "liikenne".
   * @param array $data
   *   Decoded JSON data from Request.
   * @param int $weight
   *   Project weight.
   *
   * @throws \JsonException
   */
  public function __construct(string $project_name, array $data, int $weight) {
    if (!$project_name) {
      throw new \JsonException('Project name does not exist in menu data for ');
    }

    if (!array_key_exists('menu_tree', $data)) {
      throw new \JsonException('Menu data does not exist for project' . $project_name);
    }

    if (!array_key_exists('site_name', $data) && is_array($data['site_name'])) {
      throw new \JsonException('Site name(s) does not exist in menu data for ' . $project_name);
    }

    $this->projectName = $project_name;
    $this->menuTree = $this->processWeight($data['menu_tree'], $weight);
    $this->siteName = $data['site_name'];
  }

  /**
   * Get Menu tree.
   *
   * @param string|null $lang_code
   *   Language code.
   *
   * @return array|false
   *   Returns either menu tree by given language code or FALSE.
   */
  public function getMenuTree(string $lang_code = NULL): array|FALSE {
    if (!empty($lang_code) && array_key_exists($lang_code, $this->menuTree)) {
      return $this->menuTree[$lang_code];
    }
    return FALSE;
  }

  /**
   * Get site name.
   *
   * @param string $lang_code
   *   Language code.
   *
   * @return string
   *   Returns site name by given language code.
   */
  public function getSiteName(string $lang_code): string {
    if (array_key_exists($lang_code, $this->siteName)) {
      return $this->siteName[$lang_code];
    }
    return $this->siteName['fi'];
  }

  /**
   * Get project identifier.
   *
   * @return string
   *   Returns project name.
   */
  public function getProjectName(): string {
    return $this->projectName;
  }

  /**
   * Process menu tree weight.
   *
   * @param array $menu_tree
   *   Menu tree.
   * @param int $weight
   *   Project weight.
   *
   * @return array
   *   Returns the processed menu tree.
   */
  protected function processWeight(array $menu_tree, int $weight = 0): array {
    foreach ($menu_tree as &$branch) {
      $branch['weight'] = $weight;
    }
    return $menu_tree;
  }

  /**
   * Create the response for global navigation menu requests.
   *
   * @param string $menu_type
   *   Type of the menu.
   * @param string $language_id
   *   Current language.
   * @param array $global_menus
   *   Array of global menu objects.
   * @param int $current_time
   *   When the response was created.
   *
   * @return array
   *   Return value.
   */
  public static function createResponse(string $menu_type, string $language_id, array $global_menus, int $current_time): array {
    $menus = [];
    foreach ($global_menus as $global_menu) {
      if ($global_menu->hasTranslation($language_id)) {
        $menu = $global_menu->getTranslation($language_id);
        if ($menu_type === 'main') {
          $menus[] = json_decode($menu->menu_tree->value);
        }
        else {
          $menus = json_decode($menu->menu_tree->value);
        }
      }
    }

    return [
      'lang_code' => $language_id,
      'created_at' => $current_time,
      'menu_type' => $menu_type,
      'menu_tree' => $menus,
    ];
  }

}
