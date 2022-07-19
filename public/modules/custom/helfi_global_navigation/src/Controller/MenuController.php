<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\helfi_navigation\Menu\Menu;
use Drupal\helfi_global_navigation\Entity\GlobalMenu;
use Drupal\helfi_global_navigation\MenuRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for global menu entities.
 */
class MenuController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Site default language code.
   *
   * @var string
   */
  private string $defaultLanguageId;

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
    LanguageManagerInterface $languageManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->defaultLanguageId = $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * Return all global menu entities.
   *
   * @param string|null $menu_type
   *   Menu type.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns a JSON response of requested menu type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \JsonException
   */
  public function list(string $menu_type = NULL): JsonResponse {
    if (!Menu::menuExists($menu_type)) {
      throw new \JsonException('Requested menu type doesn\'t exist.');
    }

    $storage = $this->entityTypeManager->getStorage('global_menu');

    $language_id = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $global_menus = $storage->loadByProperties([
      'menu_type' => $menu_type,
      'langcode' => $this->defaultLanguageId,
    ]);

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

    return new JsonResponse([
      'lang_code' => $language_id,
      'menu_type' => $menu_type,
      'menu_tree' => $menus,
    ]);
  }

  /**
   * Create or update menu entity.
   *
   * @param string $project_name
   *   Project name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The resulting menu entity.
   *
   * @throws \JsonException
   * @throws \WebDriver\Exception\JsonParameterExpected
   */
  public function post(string $project_name, Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $weight = GlobalMenu::getProjectWeight($project_name);
    $menu_request = new MenuRequest($project_name, $data, $weight);

    // Retrieve existing global menu entities.
    $storage = $this->entityTypeManager->getStorage('global_menu');
    $existing = $storage->loadByProperties([
      'project' => $project_name,
      'menu_type' => Menu::MAIN_MENU,
      'langcode' => $this->defaultLanguageId,
    ]);

    try {
      if (!empty($existing)) {
        $this->updateMenu($existing, $menu_request);
      }
      else {
        $this->createNewMenu($project_name, $menu_request);
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

    return new JsonResponse([], 201);
  }

  /**
   * Create Global menu entity for each language for the first time.
   *
   * @param string $project_name
   *   Project name. Eg. "liikenne".
   * @param \Drupal\helfi_global_navigation\MenuRequest $menu_request
   *   Project menu class.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNewMenu(string $project_name, MenuRequest $menu_request): void {
    $menu = GlobalMenu::create([
      'language' => $this->defaultLanguageId,
      'project' => $project_name,
      'menu_type' => Menu::MAIN_MENU,
      'site_name' => $menu_request->getSiteName($this->defaultLanguageId),
      'weight' => GlobalMenu::getProjectWeight($menu_request->getProjectName()),
      'menu_tree' => json_encode($menu_request->getMenuTree($this->defaultLanguageId)),
    ]);
    $menu->save();

    foreach ($this->languageManager()->getLanguages() as $language) {
      $lang_code = $language->getId();
      if ($lang_code === $this->defaultLanguageId) {
        continue;
      }

      $menu->addTranslation($lang_code)
        ->set('site_name', $menu_request->getSiteName($lang_code))
        ->set('menu_tree', json_encode($menu_request->getMenuTree($lang_code)))
        ->set('menu_type', Menu::MAIN_MENU)
        ->save();
    }
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
  protected function updateMenu(array $global_menus, MenuRequest $menu_request): void {
    /** @var \Drupal\helfi_global_navigation\Entity\GlobalMenu $menu_entity */
    $menu_entity = reset($global_menus);
    $menu_tree = $menu_request->getMenuTree($this->defaultLanguageId);

    $menu_entity
      ->set('menu_tree', json_encode($menu_tree))
      ->set('weight', GlobalMenu::getProjectWeight($menu_request->getProjectName()))
      ->set('site_name', $menu_request->getSiteName($this->defaultLanguageId))
      ->save();

    foreach ($this->languageManager()->getLanguages() as $language) {
      $lang_code = $language->getId();
      if ($lang_code === $this->defaultLanguageId) {
        continue;
      }

      $translation = $menu_entity->hasTranslation($lang_code)
        ? $menu_entity->getTranslation($lang_code)
        : $menu_entity->addTranslation($lang_code);

      $translation
        ->set('site_name', $menu_request->getSiteName($lang_code))
        ->set('menu_tree', json_encode($menu_request->getMenuTree($lang_code)))
        ->set('menu_type', Menu::MAIN_MENU)
        ->save();
    }
  }

}
