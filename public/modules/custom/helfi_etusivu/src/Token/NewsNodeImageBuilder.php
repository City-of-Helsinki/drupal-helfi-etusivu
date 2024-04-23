<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Token;

use Drupal\Core\Entity\EntityInterface;
use Drupal\helfi_platform_config\Token\OGImageBuilderInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;

/**
 * OG image for nodes.
 */
class NewsNodeImageBuilder implements OGImageBuilderInterface {

  /**
   * {@inheritDoc}
   */
  public function applies(?EntityInterface $entity): bool {
    return $entity instanceof NodeInterface;
  }

  /**
   * {@inheritDoc}
   */
  public function buildUri(?EntityInterface $entity): ?string {
    assert($entity instanceof NodeInterface);

    if (
      $entity->hasField('field_main_image') &&
      isset($entity->field_main_image->entity) &&
      $entity->field_main_image->entity instanceof MediaInterface &&
      $entity->field_main_image->entity->hasField('field_media_image')
    ) {
      // If main image has an image set, use it as the shareable image.
      // @phpstan-ignore-next-line
      $image_entity = $entity->get('field_main_image')->entity->field_media_image;

      // Skip current entity if it's empty.
      if (!$image_entity->isEmpty()) {
        return $image_entity->entity->getFileUri();
      }
    }

    return NULL;
  }

}
