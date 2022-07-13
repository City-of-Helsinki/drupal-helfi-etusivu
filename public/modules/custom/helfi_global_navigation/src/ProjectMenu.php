<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation;

/**
 * Wrapper class for JSON request.
 */
class ProjectMenu {

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
   * Constructor for ProjectMenu object.
   *
   * @param string $project_name
   *   Project name. Eg. "liikenne".
   * @param array $data
   *   Decoded JSON data from Request.
   *
   * @throws \JsonException
   */
  public function __construct(string $project_name, array $data) {
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
    $this->menuTree = $data['menu_tree'];
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

}
