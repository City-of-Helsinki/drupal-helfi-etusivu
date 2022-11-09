<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Entity\Storage;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\helfi_global_navigation\Entity\GlobalMenu;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a storage class for global navigation entities.
 */
final class GlobalMenuStorage extends SqlContentEntityStorage {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  private EntityRepositoryInterface $entityRepository;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) : self {
    $instance = parent::createInstance($container, $entity_type);
    $instance->entityRepository = $container->get('entity.repository');
    return $instance;
  }

  /**
   * Create a new entity by ID.
   *
   * @param string $id
   *   The id.
   *
   * @return \Drupal\helfi_global_navigation\Entity\GlobalMenu
   *   The created entity.
   */
  public function createById(string $id) : GlobalMenu {
    return $this->create([
      $this->getEntityType()->getKey('id') => $id,
    ]);
  }

  /**
   * Sort and load all global menu entities..
   *
   * @return \Drupal\helfi_global_navigation\Entity\GlobalMenu[]
   *   An array of global menu entities.
   */
  public function loadMultipleSorted(
    array $conditions = [],
    bool $forceCurrentLanguage = TRUE,
  ) : array {
    $query = $this->getQuery()
      ->accessCheck();

    // Only load entities for current language.
    if ($forceCurrentLanguage) {
      $conditions[$this->langcodeKey] = $this->languageManager
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
    }
    foreach ($conditions as $key => $value) {
      $query->condition($key, $value, '=');
    }
    // Sort by weight and then by project name.
    $query->sort('weight')
      ->sort('name');

    $ids = $query->execute();

    if ($ids) {
      if ($forceCurrentLanguage) {
        return $this->entityRepository->getActiveMultiple($this->entityTypeId, $ids);
      }
      return $this->loadMultiple($ids);
    }
    return [];
  }

}
