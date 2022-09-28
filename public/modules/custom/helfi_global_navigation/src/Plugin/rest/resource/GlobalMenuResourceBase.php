<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\rest\resource;

use Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for global menu resources.
 */
abstract class GlobalMenuResourceBase extends MenuResourceBase {

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

}
