<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\helfi_global_navigation\Entity\GlobalMenu;
use Drupal\helfi_global_navigation\MenuRequest;
use Drupal\helfi_global_navigation\MenuRequestHandler;
use Drupal\helfi_global_navigation\MenuResponseHandler;
use Drupal\helfi_navigation\Menu\Menu;
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
   * @param \Drupal\helfi_global_navigation\MenuRequestHandler $menuRequestHandler
   *   Menu request handler.
   * @param \Drupal\helfi_global_navigation\MenuResponseHandler $menuResponseHandler
   *   Menu response handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language manager.
   */
  public function __construct(
    private MenuRequestHandler $menuRequestHandler,
    private MenuResponseHandler $menuResponseHandler,
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('helfi_global_navigation.menu_request_handler'),
      $container->get('helfi_global_navigation.menu_response_handler'),
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

    $language_id = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $menu = $this->menuResponseHandler->getMenuResponse($menu_type, $language_id);

    $cache['#cache'] = [
      'tags' => [
        "config:system.menu.$menu_type",
      ],
      'contexts' => [
        'languages:language_content',
      ],
    ];

    $response = new CacheableJsonResponse($menu, 201);
    $response->addCacheableDependency(
      CacheableMetadata::createFromRenderArray($cache)
    );

    return $response;
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
   */
  public function post(string $project_name, Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $weight = GlobalMenu::getProjectWeight($project_name);
    $menu_request = new MenuRequest($project_name, $data, $weight);

    $this->menuRequestHandler->handleRequest($menu_request);

    return new JsonResponse([], 201);
  }

}
