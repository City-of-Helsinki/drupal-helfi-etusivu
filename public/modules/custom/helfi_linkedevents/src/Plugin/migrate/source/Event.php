<?php

declare(strict_types=1);

namespace Drupal\helfi_linkedevents\Plugin\migrate\source;

/**
 * Source plugin for retrieving data from Linked Events.
 *
 * @MigrateSource(
 *   id = "linkedevents_event"
 * )
 */
class Event extends LinkedEvents {

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'LinkedEventsEvent';
  }

}
