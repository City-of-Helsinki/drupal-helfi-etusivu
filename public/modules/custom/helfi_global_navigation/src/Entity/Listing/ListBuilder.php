<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Entity\Listing;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller to global menu entity types.
 */
class ListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() : array {
    $header['id'] = $this->t('ID');
    $header['project'] = $this->t('Project');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) : array {
    $row['id'] = $entity->id();
    $row['project'] = $entity->toLink($entity->get('project')->value);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) : array {
    $operations = parent::getDefaultOperations($entity);
    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }
    return $operations;
  }

}
