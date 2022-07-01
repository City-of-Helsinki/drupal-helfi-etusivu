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
      if ($tree = $menu->get('menu_tree')->value) {
        return json_decode($tree);
      }
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

    $existing = GlobalMenu::load($id);

    if ($existing) {
      

      $langcode = $data->langcode;
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
