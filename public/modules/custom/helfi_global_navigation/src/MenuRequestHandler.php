<?php

declare(strict_types=1);

namespace Drupal\helfi_global_navigation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_global_navigation\Entity\GlobalMenu;
use Drupal\helfi_navigation\Menu\Menu;

/**
 * Wrapper class for JSON request.
 */
class MenuRequestHandler {

  /**
   * Default language id.
   *
   * @var string
   */
  private string $defaultLanguageId;

  /**
   * Language manager.
   *
   * @var LanguageManagerInterface
   */
  private LanguageManagerInterface $languageManager;

  /**
   * EntityType Manager.
   *
   * @var EntityTypeManagerInterface
   *
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a MenuController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager,
    private MenuCache $menuCache
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->defaultLanguageId = $this->languageManager->getDefaultLanguage()->getId();
  }

  public function handleRequest(MenuRequest $menu_request): void {
    // Retrieve existing global menu entities.
    $storage = $this->entityTypeManager->getStorage('global_menu');
    $existing = $storage->loadByProperties([
      'project' => $menu_request->getProjectName(),
      'menu_type' => Menu::MAIN_MENU,
      'langcode' => $this->defaultLanguageId,
    ]);

    try {
      if (empty($existing)) {
        $menu = $this->createNewMenu($menu_request);
      }
      else {
        $menu = $this->updateMenu($existing, $menu_request);
      }

      $this->setCache(
        $menu->getMenuType(),
        $this->languageManager->getCurrentLanguage()->getId()
      );
    }
    catch (\Exception $exception) {
      throw new \JsonException(sprintf(
        '%s in %s on line %s',
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
      ));
    }
  }

  /**
   * Create Global menu entity for each language for the first time.
   *
   * @param \Drupal\helfi_global_navigation\MenuRequest $menu_request
   *   Project menu class.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNewMenu(MenuRequest $menu_request): GlobalMenu {
    $menu = GlobalMenu::create([
      'language' => $this->defaultLanguageId,
      'project' => $menu_request->getProjectName(),
      'menu_type' => Menu::MAIN_MENU,
      'site_name' => $menu_request->getSiteName($this->defaultLanguageId),
      'weight' => GlobalMenu::getProjectWeight($menu_request->getProjectName()),
      'menu_tree' => json_encode($menu_request->getMenuTree($this->defaultLanguageId)),
    ]);
    $menu->save();

    foreach (array_keys($this->languageManager->getLanguages()) as $lang_code) {
      if ($lang_code === $this->defaultLanguageId) {
        continue;
      }

      $menu->addTranslation($lang_code)
        ->set('site_name', $menu_request->getSiteName($lang_code))
        ->set('menu_tree', json_encode($menu_request->getMenuTree($lang_code)))
        ->set('menu_type', Menu::MAIN_MENU)
        ->save();
    }
    return $menu;
  }

  /**
   * Update existing global menu entity.
   *
   * @param array $global_menus
   *   Translated global menu entities as array.
   * @param \Drupal\helfi_global_navigation\MenuRequest $menu_request
   *   Project menu class.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateMenu(array $global_menus, MenuRequest $menu_request): GlobalMenu {
    /** @var \Drupal\helfi_global_navigation\Entity\GlobalMenu $menu */
    $menu = reset($global_menus);
    $menu_tree = $menu_request->getMenuTree($this->defaultLanguageId);

    $menu
      ->set('menu_tree', json_encode($menu_tree))
      ->set('weight', GlobalMenu::getProjectWeight($menu_request->getProjectName()))
      ->set('site_name', $menu_request->getSiteName($this->defaultLanguageId))
      ->save();

    foreach ($this->languageManager->getLanguages() as $language) {
      $lang_code = $language->getId();
      if ($lang_code === $this->defaultLanguageId) {
        continue;
      }

      $translation = $menu->hasTranslation($lang_code)
        ? $menu->getTranslation($lang_code)
        : $menu->addTranslation($lang_code);

      $translation
        ->set('site_name', $menu_request->getSiteName($lang_code))
        ->set('menu_tree', json_encode($menu_request->getMenuTree($lang_code)))
        ->set('menu_type', Menu::MAIN_MENU)
        ->save();
    }
    return $menu;
  }

  /**
   * Set main menu response to cache.
   *
   * @param string $menu_type
   *   Menu type.
   * @param string $lang_code
   *   Language code id.
   *
   * @return void
   */
  private function setCache(string $menu_type, string $lang_code): void{
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
  }

}
