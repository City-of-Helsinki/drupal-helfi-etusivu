<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Node;

use Drupal\helfi_etusivu_entities\Plugin\ExternalEntities\StorageClient\Announcements;
use Drupal\node\Entity\Node;

/**
 * A bundle class for Announcement-node.
 */
final class Announcement extends Node implements PublishExternallyInterface {

  use PublishExternallyTrait;

  /**
   * {@inheritdoc}
   */
  public function getExternalCacheTags(): array {
    return [Announcements::$customCacheTag];
  }

}
