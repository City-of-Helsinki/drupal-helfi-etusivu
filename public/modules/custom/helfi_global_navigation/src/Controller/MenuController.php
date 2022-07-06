<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Controller;

use _PHPStan_43cb6abb8\Nette\Utils\JsonException;
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
    $existing = $storage->loadByProperties(['project' => $project_name, 'langcode' => $this->default_language_id]);

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
    $menu = GlobalMenu::create([
      'language' => $this->default_language_id,
      'project' => $project_name,
      'name' => $project->getSiteName($this->default_language_id),
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
   */
  protected function updateMenu(array $global_menus, ProjectMenu $project): void {
    /** @var \Drupal\helfi_global_navigation\Entity\GlobalMenu $menu_entity */
    $menu_entity = reset($global_menus);
    $menu_tree = $project->getMenuTree($this->default_language_id)

    $menu_entity
      ->set('menu_tree', json_encode($menu_tree))
      ->set('name', $project->getSiteName($this->default_language_id))
      ->save();

    foreach ($this->languageManager()->getLanguages() as $language) {
      $lang_code = $language->getId();
      if ($lang_code === $this->default_language_id) {
        continue;
      }

      $translation = $menu_entity->hasTranslation($lang_code) ?
        $menu_entity->getTranslation($lang_code) : $menu_entity->addTranslation($lang_code);

      $translation
        ->set('name', $project->getSiteName($lang_code))
        ->set('menu_tree', json_encode($project->getMenuTree($lang_code)))
        ->save();
    }
  }

}
