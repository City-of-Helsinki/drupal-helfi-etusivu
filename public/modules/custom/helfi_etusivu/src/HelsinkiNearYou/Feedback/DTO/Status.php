<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * An enum to store feedback status.
 */
enum Status : string {

  case Ready = 'READY';
  case Processing = 'PROCESSING';
  case Unknown = 'UNKNOWN';

  /**
   * Gets the status label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The status label.
   */
  public function label() : TranslatableMarkup {
    return match($this) {
      self::Ready => new TranslatableMarkup('Ready', [], ['context' => 'Helsinki near you status label']),
      self::Processing => new TranslatableMarkup('Processing', [], ['context' => 'Helsinki near you status label']),
      self::Unknown => new TranslatableMarkup('Unknown', [], ['context' => 'Helsinki near you status label']),
    };
  }

}
