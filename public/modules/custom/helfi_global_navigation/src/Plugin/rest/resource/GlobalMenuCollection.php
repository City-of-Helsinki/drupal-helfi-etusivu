<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\helfi_global_navigation\Entity\GlobalMenu;
use Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Represents Global menu records as resources.
 *
 * @RestResource(
 *   id = "helfi_global_menu_collection",
 *   label = @Translation("Global menu - Collection"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/global-menu",
 *   }
 * )
 */
final class GlobalMenuCollection extends GlobalMenuBase {

  /**
   * The entity storage.
   *
   * @var \Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage
   */
  protected GlobalMenuStorage $storage;

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
    $instance->storage = $container->get('entity_type.manager')->getStorage('global_menu');
    return $instance;
  }

  /**
   * Callback for GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response.
   */
  public function get(Request $request): ResourceResponse {
    $cacheableMetadata = (new CacheableMetadata())
      ->addCacheableDependency($request->attributes->get(AccessAwareRouterInterface::ACCESS_RESULT));

    $entities = array_map(function (GlobalMenu $entity) use ($cacheableMetadata) : GlobalMenu {
      $cacheableMetadata->addCacheableDependency($entity);

      return $this->entityRepository
        ->getTranslationFromContext($entity, $this->getCurrentLanguageId());
    }, $this->storage->loadMultipleSorted());
    $response = new ResourceResponse((object) $entities, 200);
    $response->addCacheableDependency($cacheableMetadata);

    return $response;
  }

}
