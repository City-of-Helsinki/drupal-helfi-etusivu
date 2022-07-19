<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\helfi_global_navigation\MenuRequestHandler;
use Drupal\helfi_global_navigation\MenuResponseHandler;
use Drupal\helfi_navigation\Menu\Menu;
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
    private MenuRequestHandler $menuRequestHandler,
    private MenuResponseHandler $menuResponseHandler,
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager,
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
      $container->get('helfi_global_navigation.menu_request_handler'),
      $container->get('helfi_global_navigation.menu_response_handler'),
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

    $language_id = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $response = $this->menuResponseHandler->getMenuResponse($menu_type, $language_id);
    return new JsonResponse($response, 201);
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
    $menu_request = new MenuRequest($project_name, $data);

    $this->menuRequestHandler->handleRequest($menu_request);

    return new JsonResponse([], 201);
  }



}
