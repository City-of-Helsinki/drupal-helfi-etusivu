<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\helfi_global_navigation\Entity\GlobalMenu;
use GuzzleHttp\json_decode;
use GuzzleHttp\json_encode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for global menu entities.
 */
class MenuController extends ControllerBase {

  /**
   * Return all global menu entities.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   List of global menu entities.
   */
  public function list(): JsonResponse {
    $menus = array_map(function ($menu) {
      $menuResponse = [
        'id' => $menu->id(),
        'name' => $menu->get('name')->value,
      ];

      if ($tree = $menu->get('menu_tree')->value) {
        $menuResponse['tree'] = json_decode($menu->get('menu_tree')->value);
      }

      return $menuResponse;
    }, GlobalMenu::loadMultiple());

    return new JsonResponse($menus);
  }

  /**
   * Create or update menu entity.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   The resulting menu entity.
   */
  public function post(string $id, Request $request): JsonResponse {
    $data = json_decode($request->getContent());

    $existingId = \Drupal::entityQuery('global_menu')
      ->condition('project', $id)
      ->range(0, 1)
      ->execute();

    if (!empty($existingId)) {
      $existing = GlobalMenu::load(reset($existingId));
      $existing->name = $data->name;
      $existing->menu_tree = json_encode($data->menu_tree);
      $existing->save();

      return new JsonResponse($existing);
    }

    $menu = GlobalMenu::create([
      'project' => $id,
      'name' => $data->name,
      'menu_tree' => json_encode($data->menu_tree),
    ]);

    $menu->save();

    return new JsonResponse($menu, 201);
  }

}
