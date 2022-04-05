<?php

declare(strict_types = 1);

namespace Drupal\helfi_etusivu\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Represents News as resources.
 *
 * @RestResource(
 *   id = "helfi_etusivu_news",
 *   label = @Translation("Etusivu: News"),
 *   uri_paths = {
 *     "collection" = "/api/v1/news",
 *     "canonical" = "/api/v1/news/{entity}"
 *   }
 * )
 */
final class News extends EntityResource {

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
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function routes() : array {
    $collection = parent::routes();
    $definition = $this->getPluginDefinition();

    $collection_path = $definition['uri_paths']['collection'];

    return $collection;
  }

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the record.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get(EntityInterface $entity, Request $request) : ResourceResponse {
  }

}
