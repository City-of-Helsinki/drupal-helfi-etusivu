<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Node;

use Drupal\helfi_etusivu_entities\Plugin\ExternalEntities\StorageClient\Announcements;
use Drupal\helfi_node_announcement\Entity\Announcement as AnnouncementNodeEntity;

/**
 * A bundle class for Announcement-node.
 */
final class Announcement extends AnnouncementNodeEntity implements PublishExternallyInterface {

  use PublishExternallyTrait;

  /**
   * {@inheritdoc}
   */
  public function getExternalCacheTags(): array {
    return [Announcements::$customCacheTag];
  }

}
