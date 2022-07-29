<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_global_navigation\Entity\GlobalMenu as GlobalMenuEntity;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouteCollection;

/**
 * Represents Global menu records as resources.
 *
 * @RestResource(
 *   id = "helfi_global_menu",
 *   label = @Translation("Global menu entity"),
 *   entity_type = "global_menu",
 *   uri_paths = {
 *     "canonical" = "/api/v1/global-menu/{entity}",
 *     "collection" = "/api/v1/global-menu"
 *   }
 * )
 */
final class GlobalMenu extends ResourceBase {

  use EntityResourceValidationTrait;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  private EntityRepositoryInterface $entityRepository;

  /**
   * The langugage manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private LanguageManagerInterface $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->entityRepository = $container->get('entity.repository');
    $instance->languageManager = $container->get('language_manager');

    return $instance;
  }

  /**
   * Callback for GET requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response.
   */
  public function get(Request $request) : ResourceResponse {
    $langcode = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->setCacheMaxAge(0);

    if (!$id = $request->attributes->get('entity')) {
      $entities = array_map(function (GlobalMenuEntity $entity) use ($cacheableMetadata, $langcode) : GlobalMenuEntity {
        $cacheableMetadata->addCacheableDependency($entity);

        return $this->entityRepository->getTranslationFromContext($entity, $langcode);
      }, GlobalMenuEntity::loadMultiple());
      $response = new ResourceResponse($entities, 200);
      $response->addCacheableDependency($cacheableMetadata);

      return $response;
    }

    if (!$entity = GlobalMenuEntity::load($id)) {
      throw new NotFoundHttpException();
    }
    $entity = $this->entityRepository->getTranslationFromContext($entity, $langcode);
    $response = (new ResourceResponse($entity, 200))
      ->addCacheableDependency($entity);
    $response->addCacheableDependency($cacheableMetadata);
    return $response;
  }

  /**
   * Callback for POST requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function patch(Request $request) : ModifiedResourceResponse {
    $id = $request->attributes->get('entity');

    if (!$entity = GlobalMenuEntity::load($id)) {
      throw new NotFoundHttpException();
    }
    try {
      $content = \GuzzleHttp\json_decode($request->getContent());
    }
    catch (\InvalidArgumentException) {
      throw new BadRequestHttpException('Invalid JSON.');
    }

    foreach (['menu_tree', 'site_name'] as $required) {
      if (!isset($content->{$required})) {
        throw new BadRequestHttpException(sprintf('Missing required: %s', $required));
      }
    }
    $langcode = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();

    /** @var \Drupal\helfi_global_navigation\Entity\GlobalMenu $entity */
    $entity = $entity->hasTranslation($langcode) ?
      $entity->getTranslation($langcode) :
      $entity->addTranslation($langcode);

    $entity->setMenuTree($content->menu_tree)
      ->setLabel($content->site_name);

    $this->validate($entity);
    $entity->save();

    return new ModifiedResourceResponse($entity, 201);
  }

}
