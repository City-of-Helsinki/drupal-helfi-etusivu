<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Node;

/**
 * A trait to publish nodes externally through 'field_publish_externally' field.
 */
trait PublishExternallyTrait {

  /**
   * Whether the announcement should be published to all sites or not.
   *
   * @return bool
   *   TRUE if the entity should be published externally.
   */
  public function publishExternally() : bool {
    return (bool) $this->get('field_publish_externally')->value;
  }

  /**
   * Whether to publish entity externally or not.
   *
   * @param bool $status
   *   TRUE if the entity should be published externally.
   *
   * @return $this
   *   The self.
   */
  public function setPublishExternally(bool $status): self {
    $this->set('field_publish_externally', $status);
    return $this;
  }

}
