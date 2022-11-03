<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\rest\resource;

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
    $cacheableMetadata = $this->getCacheableMetaData($request);

    $entities = array_map(function (GlobalMenu $entity) use ($cacheableMetadata, $langcode, $request) : GlobalMenu {
      $entity = $entity->getTranslation($langcode);
      $cacheableMetadata->addCacheableDependency($entity);

      return $this->processEntityFilters($entity, $request);
    }, $this->storage->loadMultipleSorted(['status' => 1]));
    $response = new ResourceResponse($entities, 200);
    $response->addCacheableDependency($cacheableMetadata);

    return $response;
  }

}
