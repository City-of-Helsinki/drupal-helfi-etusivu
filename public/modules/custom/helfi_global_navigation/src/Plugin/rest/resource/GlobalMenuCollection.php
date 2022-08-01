<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\helfi_global_navigation\Entity\GlobalMenu as GlobalMenuEntity;
use Drupal\rest\ResourceResponse;

/**
 * Represents Global menu records as resources.
 *
 * @RestResource(
 *   id = "helfi_global_menu_collection",
 *   label = @Translation("Global menu entity - Collection"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/global-menu",
 *   }
 * )
 */
final class GlobalMenuCollection extends GlobalMenuBase {

  /**
   * Callback for GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response.
   */
  public function get(): ResourceResponse {
    $langcode = $this->getCurrentLanguageId();
    $cacheableMetadata = new CacheableMetadata();

    $entities = array_map(function (GlobalMenuEntity $entity) use ($cacheableMetadata, $langcode) : GlobalMenuEntity {
      $cacheableMetadata->addCacheableDependency($entity);

      return $this->entityRepository->getTranslationFromContext($entity, $langcode);
    }, GlobalMenuEntity::loadMultiple());
    $response = new ResourceResponse($entities, 200);
    $response->addCacheableDependency($cacheableMetadata);

    return $response;
  }

}
