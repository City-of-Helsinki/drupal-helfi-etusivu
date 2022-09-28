<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * A base class for global menu resources.
 */
abstract class MenuResourceBase extends ResourceBase {

  use EntityResourceValidationTrait;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

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
    $instance->languageManager = $container->get('language_manager');

    return $instance;
  }

  /**
   * Asserts entity permissions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param string $operation
   *   The entity operation.
   *
   * @return $this
   *   The self.
   */
  protected function assertPermission(EntityInterface $entity, string $operation) : static {
    $access = $entity->access($operation, return_as_object: TRUE);

    if (!$access->isAllowed()) {
      throw new AccessDeniedHttpException("You are not authorized to {$operation} this {$entity->getEntityTypeId()} entity");
    }
    return $this;
  }

  /**
   * Gets the current language ID.
   *
   * @return string
   *   The language ID.
   */
  protected function getCurrentLanguageId() : string {
    return $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
  }

}
