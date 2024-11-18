<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Node;

/**
 * An interface to publish nodes externally.
 */
interface PublishExternallyInterface {

  /**
   * Whether the announcement should be published to all sites or not.
   *
   * @return bool
   *   TRUE if the entity should be published externally.
   */
  public function publishExternally() : bool;

  /**
   * Gets the external cache tags to invalidate.
   *
   * @return array
   *   The cache tags.
   */
  public function getExternalCacheTags() : array;

}
