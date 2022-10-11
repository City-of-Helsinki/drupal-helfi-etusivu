<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\helfi_navigation\Menu\MenuTreeBuilder;
use Drupal\rest\ResourceResponse;
use Drupal\system\Entity\Menu;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Represents Menu link content records as resources.
 *
 * @RestResource(
 *   id = "helfi_menu_link_collection",
 *   label = @Translation("Menu - Collection"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/menu/{menu_name}",
 *   }
 * )
 */
final class MenuLinkCollection extends MenuResourceBase {

  /**
   * The menu tree builder.
   *
   * @var \Drupal\helfi_navigation\Menu\MenuTreeBuilder
   */
  private MenuTreeBuilder $treeBuilder;

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
    $instance->treeBuilder = $container->get('helfi_navigation.menu_tree_builder');

    return $instance;
  }

  /**
   * Callback for GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response.
   */
  public function get(Request $request): ResourceResponse {
    if (!($menuName = $request->attributes->get('menu_name')) || !$menu = Menu::load($menuName)) {
      throw new NotFoundHttpException();
    }
    $langcode = $this->getCurrentLanguageId();
    $cacheableMetadata = (new CacheableMetadata())
      ->addCacheableDependency($request->attributes->get(AccessAwareRouterInterface::ACCESS_RESULT))
      ->addCacheableDependency($menu);

    try {
      $tree = $this->treeBuilder
        ->build($menuName, $langcode);
      $response = new ResourceResponse($this->toArray($tree), 200);
      $response->addCacheableDependency($cacheableMetadata);

      return $response;
    }
    catch (\Exception) {
    }
    throw new BadRequestHttpException(sprintf('Failed to load menu: %s', $menuName));
  }

  /**
   * Converts menu tree objects to array.
   *
   * @param \stdClass[] $items
   *   The items.
   *
   * @return array
   *   The response converted to array.
   */
  private function toArray(array $items) : array {
    // The menu tree builder returns an array of stdClass objects, but
    // Drupal doesn't know how to normalize them. Creating a generic
    // normalizer for stdClass might have some unintended consequences, so
    // we "normalize" them by converting them to json and back to array.
    $json = \json_encode($items, \JSON_THROW_ON_ERROR);
    return \json_decode($json, TRUE, flags: \JSON_THROW_ON_ERROR);
  }

}
