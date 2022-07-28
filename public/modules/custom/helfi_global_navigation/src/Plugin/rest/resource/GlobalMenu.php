<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\helfi_global_navigation\Entity\GlobalMenu as GlobalMenuEntity;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait;
use Drupal\rest\ResourceResponse;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Represents Global menu records as resources.
 *
 * @RestResource(
 *   id = "helfi_global_menu",
 *   label = @Translation("Global menu entity"),
 *   entity_type = "global_menu",
 *   uri_paths = {
 *     "canonical" = "/api/v1/global-menu/{entity}",
 *     "create" = "/api/v1/global-menu",
 *     "collection" = "/api/v1/global-menu"
 *   }
 * )
 */
final class GlobalMenu extends ResourceBase {

  use EntityResourceValidationTrait;

  private Validator $validator;

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
    $instance->validator = new Validator();

    return $instance;
  }

  protected function collection(Request $request) : ResourceResponse {
    $entities = GlobalMenuEntity::loadMultiple();
    $response = new ResourceResponse($entities, 200);

    array_map(fn (GlobalMenuEntity $entity) => $response->addCacheableDependency($entity), $entities);
    $response->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));

    return $response;
  }

  public function get(Request $request) : ResourceResponse {
    if (!$id = $request->attributes->get('entity')) {
      return $this->collection($request);
    }

    if (!$entity = GlobalMenuEntity::load($id)) {
      throw new NotFoundHttpException();
    }
    $response = (new ResourceResponse($entity, 200))
      ->addCacheableDependency($entity);
    $response->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
    return $response;
  }

  public function patch(Request $request) : ModifiedResourceResponse {
    $id = $request->attributes->get('entity');

    if (!$entity = GlobalMenuEntity::load($id)) {
      throw new NotFoundHttpException();
    }

    if (!$content = $request->getContent()) {
      throw new BadRequestHttpException('Invalid content');
    }
    try {
      $content = \GuzzleHttp\json_decode($content, TRUE);
    }
    catch (\InvalidArgumentException $e) {
      throw new BadRequestHttpException('Invalid JSON.');
    }
    $entity->set('menu_tree', $content);
    $this->validate($entity);
    $entity->save();

    return new ModifiedResourceResponse($entity, 201);
  }

  public function routes() {
    $collection = parent::routes();

    $definition = $this->getPluginDefinition();
    $route = $this->getBaseRoute($definition['uri_paths']['collection'], 'GET');

    $collection->add('helfi_global_menu.collection', $route);
    return $collection;
  }

}
