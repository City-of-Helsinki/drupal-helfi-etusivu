<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Node;

use Drupal\helfi_etusivu_entities\Plugin\ExternalEntities\StorageClient\Surveys;
use Drupal\node\Entity\Node;

/**
 * A bundle class for Survey-node.
 */
final class Survey extends Node implements PublishExternallyInterface {

  use PublishExternallyTrait;

  /**
   * {@inheritdoc}
   */
  public function getExternalCacheTags(): array {
    return [Surveys::$customCacheTag];
  }

}
