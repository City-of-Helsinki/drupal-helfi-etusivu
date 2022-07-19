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
   * @var EntityStorageInterface
   */
  private EntityStorageInterface $entityStorage;

  /**
   * Default language id.
   *
   * @var string
   */
  private string $default_language;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager,
    private MenuCache $menuCache
  ) {
    $this->entityStorage = $entityTypeManager->getStorage('global_menu');
    $this->default_language = $languageManager->getDefaultLanguage()->getId();
  }

  public function getMenuResponse(string $menu_type, string $lang_code): array {
    if ($cached = $this->menuCache->getCached($menu_type, $lang_code)) {
      return $cached;
    }

    return $this->createMenuResponse($menu_type, $lang_code);
  }

  public function createMenuResponse(string $menu_type, string $lang_code): array {
    $global_menus = $this->entityStorage->loadByProperties([
      'menu_type' => $menu_type,
      'langcode' => $this->default_language,
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
        $menu = $global_menu->getTranslation($lang_code);
        $menuResponse[$menu->project->value]['menu_tree'] = $menu->getMenuTree();
        $menuResponse[$menu->project->value]['site_name'] = $menu->getSiteName();
      }
    }

    $this->menuCache->setCache($menu_type, $lang_code, $menuResponse);

    return $menuResponse;
  }

}
