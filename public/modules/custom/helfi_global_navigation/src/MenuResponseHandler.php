<?php

declare(strict_types=1);

namespace Drupal\helfi_global_navigation;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Wrapper class for JSON request.
 */
class MenuResponseHandler {

  /**
   * Entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private EntityStorageInterface $entityStorage;

  /**
   * Default language id.
   *
   * @var string
   */
  private string $defaultLanguageId;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entitytype manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language manager.
   * @param MenuCache $menuCache
   *   Menu cache.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager,
    private MenuCache $menuCache
  ) {
    $this->entityStorage = $entityTypeManager->getStorage('global_menu');
    $this->defaultLanguageId = $languageManager->getDefaultLanguage()->getId();
  }

  /**
   * Get response for menu GET request.
   *
   * @param string $menu_type
   *   Menu type.
   * @param string $lang_code
   *   Language code id.
   *
   * @return array
   *   Request response data.
   */
  public function getMenuResponse(string $menu_type, string $lang_code): array {
    if ($cached = $this->menuCache->getCached($menu_type, $lang_code)) {
      return $cached;
    }

    return $this->createMenuResponse($menu_type, $lang_code);
  }

  /**
   * Create the response.
   *
   * @param string $menu_type
   *   Menu type.
   * @param string $lang_code
   *   Language code id.
   *
   * @return array
   *   Request response data.
   */
  public function createMenuResponse(string $menu_type, string $lang_code): array {
    /** @var \Drupal\helfi_global_navigation\Entity\GlobalMenu[] $global_menus */
    $global_menus = $this->entityStorage->loadByProperties([
      'menu_type' => $menu_type,
      'langcode' => $this->defaultLanguageId,
    ]);

    $menuResponse = [];
    foreach ($global_menus as $global_menu) {
      $menuResponse[$global_menu->getProject()] = [
        'project' => $global_menu->getProject(),
        'changed' => $global_menu->changed->value,
        'weight' => $global_menu->getProjectWeight($global_menu->project->value),
        'lang_code' => $lang_code,
        'menu_type' => $menu_type,
        'menu_tree' => [],
        'site_name' => $global_menu->getSiteName(),
      ];

      if ($global_menu->hasTranslation($lang_code)) {
        /** @var \Drupal\helfi_global_navigation\Entity\GlobalMenu $menu */
        $menu = $global_menu->getTranslation($lang_code);
        $menuResponse[$menu->getProject()]['menu_tree'] = $menu->getMenuTree();
        $menuResponse[$menu->getProject()]['site_name'] = $menu->getSiteName();
      }
    }

    $this->menuCache->setCache($menu_type, $lang_code, $menuResponse);

    return $menuResponse;
  }

}
