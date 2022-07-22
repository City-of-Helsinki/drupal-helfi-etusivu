<?php

declare(strict_types=1);

namespace Drupal\helfi_global_navigation;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_global_navigation\Entity\GlobalMenu;
use Drupal\helfi_navigation\Menu\Menu;

/**
 * Handle menu creation request.
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
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private LanguageManagerInterface $languageManager;

  /**
   * EntityType Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|string
   */
  private EntityStorageInterface $storage;

  /**
   * Constructs a MenuController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->storage = $entityTypeManager->getStorage('global_menu');
    $this->defaultLanguageId = $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * Handle global navigation post request.
   *
   * @param \Drupal\helfi_navigation\Menu\MenuRequest $menu_request
   *   Menu request.
   *
   * @throws \JsonException
   */
  public function handleRequest(MenuRequest $menu_request): void {
    // Retrieve existing global menu entities.
    $existing = $this->storage->loadByProperties([
      'project' => $menu_request->getProjectName(),
      'menu_type' => Menu::MAIN_MENU,
      'langcode' => $this->defaultLanguageId,
    ]);

    try {
      if (empty($existing)) {
        $this->createNewMenu($menu_request);
      }
      else {
        $this->updateMenu($existing, $menu_request);
      }
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

}
