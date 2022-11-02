<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\rest\resource;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\helfi_global_navigation\Entity\GlobalMenu as GlobalMenuEntity;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Represents Global menu records as resources.
 *
 * @RestResource(
 *   id = "helfi_global_menu",
 *   label = @Translation("Global menu"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/global-menu/{entity}",
 *     "create" = "/api/v1/global-menu/{entity}",
 *   }
 * )
 */
final class GlobalMenu extends GlobalMenuResourceBase {

  /**
   * Gets the entity for given request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\helfi_global_navigation\Entity\GlobalMenu|null
   *   The entity or null.
   */
  private function getRequestEntity(Request $request) : ? GlobalMenuEntity {
    if (!$id = $request->attributes->get('entity')) {
      throw new BadRequestHttpException('Missing required "entity" parameter.');
    }

    if (!$entity = $this->storage->load($id)) {
      return NULL;
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() : array {
    // We check individual entity permissions later.
    return [];
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
    $langcode = $this->getCurrentLanguageId();
    $entity = $this->getRequestEntity($request);

    if (!$entity || (!$translation = $entity->getTranslation($langcode))) {
      throw new NotFoundHttpException('Entity not found.');
    }

    // @todo We should check if current user has permission to view unpublished
    // entities.
    if (!$translation->isPublished()) {
      throw new AccessDeniedHttpException();
    }
    $this->assertPermission($translation, 'view');
    $translation = $this->processEntityFilters($translation, $request);

    $cacheableMetadata = $this->getCacheableMetaData($request);
    $cacheableMetadata->addCacheableDependency($translation);

    return (new ResourceResponse($translation, 200))
      ->addCacheableDependency($cacheableMetadata);
  }

  /**
   * Callback for POST requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response.
   */
  public function post(Request $request) : ModifiedResourceResponse {
    $isNew = FALSE;
    $langcode = $this->getCurrentLanguageId();

    $content = \json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);

    $requiredFields = [];
    foreach (['menu_tree', 'site_name'] as $required) {
      if (!isset($content->{$required})) {
        $requiredFields[] = $required;
      }
    }

    if (count($requiredFields) > 0) {
      throw new UnprocessableEntityHttpException(sprintf('Missing required: %s', implode(', ', $requiredFields)));
    }

    // Attempt to create a new entity if one does not exist yet.
    if (!$entity = $this->getRequestEntity($request)) {
      $isNew = TRUE;
      $entity = $this->storage->createById($request->attributes->get('entity'))
        ->set('langcode', $langcode);
      $this->assertPermission($entity, 'create');
    }
    if (!$entity->hasTranslation($langcode)) {
      $entity = $entity->addTranslation($langcode);
      $isNew = TRUE;
    }
    else {
      $entity = $entity->getTranslation($langcode);
    }
    $this->assertPermission($entity, 'update');

    try {
      // Mark new entities as unpublished by default.
      if ($isNew) {
        $entity->setUnpublished();
      }
      // Allow entities to be published if explicitly told so.
      if (isset($content->status) && (bool) $content->status === TRUE) {
        $entity->setPublished();
      }
      $entity->setMenuTree($content->menu_tree)
        ->setLabel($content->site_name);
      $this->validate($entity);
      $entity->save();

      $responseCode = $isNew ? Response::HTTP_CREATED : Response::HTTP_OK;

      return new ModifiedResourceResponse($entity, $responseCode);
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  }

}
