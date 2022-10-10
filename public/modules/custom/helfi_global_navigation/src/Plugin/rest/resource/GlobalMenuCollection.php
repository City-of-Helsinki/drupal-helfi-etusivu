<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\helfi_global_navigation\Entity\GlobalMenu;
use Drupal\rest\ResourceResponse;
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
final class GlobalMenuCollection extends GlobalMenuResourceBase {

  /**
   * Callback for GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response.
   */
  public function get(Request $request): ResourceResponse {
    $langcode = $this->getCurrentLanguageId();
    $cacheableMetadata = (new CacheableMetadata())
      ->addCacheableDependency($request->attributes->get(AccessAwareRouterInterface::ACCESS_RESULT));

    $entities = array_map(function (GlobalMenu $entity) use ($cacheableMetadata, $langcode) : GlobalMenu {
      $entity = $entity->getTranslation($langcode);
      $cacheableMetadata->addCacheableDependency($entity);

      return $entity;
    }, $this->storage->loadMultipleSorted([
      'status' => 1,
    ]));
    $response = new ResourceResponse($entities, 200);
    $response->addCacheableDependency($cacheableMetadata);

    return $response;
  }

}
