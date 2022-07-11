<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\helfi_api_base\Menu\Menu;
use Drupal\helfi_global_navigation\Entity\GlobalMenu;
use Drupal\helfi_global_navigation\ProjectMenu;
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
  private string $default_language_id;

  /**
   * Constructs a MenuController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    protected $entityTypeManager,
    protected $languageManager
  ) {
    $this->default_language_id = $this->languageManager->getDefaultLanguage()->getId();
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
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   List of global menu entities.
   */
  public function list(string $menu_type = NULL): JsonResponse {
    if (!Menu::menuExists($menu_type)) {
      throw new \JsonException('Requested menu type doesn\'t exist.');
    }

    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('global_menu');

    $language_id = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $global_menus = $storage->loadByProperties([
      'menu_type' => $menu_type,
      'langcode' => $this->default_language_id,
    ]);

    $menus = [];
    foreach ($global_menus as $menu) {
      $menu = $menu->hasTranslation($language_id) ? $menu->getTranslation($language_id) : [];
      $menus[$menu->project->value] = [
        'project' => $menu->project->value,
        'site_name' => $menu->site_name->value,
        'changed' => $menu->changed->value,
        'menu_tree' => $menu instanceof GlobalMenu ? json_decode($menu->menu_tree->value) : [],
        'weight' => $menu->getProjectWeight($menu->project->value),
        'lang_code' => $menu->language()->getId(),
        'menu_type' => $menu_type,
      ];
    }

    return new JsonResponse($menus);
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

    $project = new ProjectMenu($project_name, $data);

    // Retrieve existing global menu entities.
    $storage = $this->entityTypeManager->getStorage('global_menu');
    $existing = $storage->loadByProperties([
      'project' => $project_name,
      'menu_type' => Menu::MAIN_MENU,
      'langcode' => $this->default_language_id,
    ]);

    try {
      if (!empty($existing)) {
        $this->updateMenu($existing, $project);
      }
      else {
        $this->createNewMenu($project_name, $project);
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
   * @param \Drupal\helfi_global_navigation\ProjectMenu $project
   *   Project menu class.
   *
   * @return void
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNewMenu(string $project_name, ProjectMenu $project): void {
    $menu = GlobalMenu::create([
      'language' => $this->default_language_id,
      'project' => $project_name,
      'menu_type' => GlobalMenu::MAIN_MENU,
      'name' => $project->getSiteName($this->default_language_id),
      'weight' => GlobalMenu::getProjectWeight($project->getProjectName()),
      'menu_tree' => json_encode($project->getMenuTree($this->default_language_id)),
    ]);
    $menu->save();

    foreach ($this->languageManager()->getLanguages() as $language) {
      $lang_code = $language->getId();
      if ($lang_code === $this->default_language_id) {
        continue;
      }

      $menu->addTranslation($lang_code)
        ->set('name', $project->getSiteName($lang_code))
        ->set('menu_tree', json_encode($project->getMenuTree($lang_code)))
        ->set('menu_type', GlobalMenu::MAIN_MENU)
        ->save();
    }
  }

  /**
   * Update existing global menu entity.
   *
   * @param array $global_menus
   *   Translated global menu entities as array.
   * @param \Drupal\helfi_global_navigation\ProjectMenu $project
   *   Project menu class.
   *
   * @return void
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateMenu(array $global_menus, ProjectMenu $project): void {
    /** @var \Drupal\helfi_global_navigation\Entity\GlobalMenu $menu_entity */
    $menu_entity = reset($global_menus);
    $menu_tree = $project->getMenuTree($this->default_language_id);

    $menu_entity
      ->set('menu_tree', json_encode($menu_tree))
      ->set('weight', GlobalMenu::getProjectWeight($project->getProjectName()))
      ->set('site_name', $project->getSiteName($this->default_language_id))
      ->save();

    foreach ($this->languageManager()->getLanguages() as $language) {
      $lang_code = $language->getId();
      if ($lang_code === $this->default_language_id) {
        continue;
      }

      $translation = $menu_entity->hasTranslation($lang_code)
        ? $menu_entity->getTranslation($lang_code)
        : $menu_entity->addTranslation($lang_code);

      $translation
        ->set('site_name', $project->getSiteName($lang_code))
        ->set('menu_tree', json_encode($project->getMenuTree($lang_code)))
        ->set('menu_type', GlobalMenu::MAIN_MENU)
        ->save();
    }
  }

}
