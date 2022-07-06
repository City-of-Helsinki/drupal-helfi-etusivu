<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
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
   * Constructs a MenuController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager
  ) {
    $this->entityTypeManager = $this->entityTypeManager ?: $entity_type_manager;
    $this->languageManager = $this->languageManager ?: $language_manager;
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
  public function list(): JsonResponse {
    $menus = array_map(function ($menu) {
      if ($tree = $menu->get('menu_tree')->value) {
        return json_decode($tree);
      }
    }, GlobalMenu::loadMultiple());

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

    // @todo Do we need a timestamp for created/updated information for GlobalMenu entity?
    $project = new ProjectMenu($project_name, $data);

    // Retrieve existing global menu entities.
    $storage = $this->entityTypeManager->getStorage('global_menu');
    $existing = $storage->loadByProperties(['project' => $project_name]);

    try {
      if (!empty($existing)) {
        $this->updateMenu($existing, $project);
      }
      else {
        $this->createNewMenu($project_name, $project);
      }
    }
    catch (\Exception $exception) {
      throw new \JsonException($exception->getMessage());
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

    foreach ($this->languageManager()->getLanguages() as $language) {

      $lang_code = $language->getId();

      $menu = GlobalMenu::create([
        'langcode' => $lang_code,
        'project' => $project_name,
        'name' => $project->getSiteName($lang_code),
        'menu_tree' => json_encode($project->getMenuTree($lang_code)),
      ]);
      $menu->save();
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
   */
  protected function updateMenu(array $global_menus, ProjectMenu $project): void {
    /** @var \Drupal\helfi_global_navigation\Entity\GlobalMenu $entity */
    foreach ($global_menus as $menu_entity) {
      $lang_code = $menu_entity->language()->getId();

      if ($menu_tree = $project->getMenuTree($lang_code)) {
        $menu_entity
          ->set('menu_tree', json_encode($menu_tree))
          ->set('name', $project->getSiteName($lang_code))
          ->save();
      }
    }
  }

}
