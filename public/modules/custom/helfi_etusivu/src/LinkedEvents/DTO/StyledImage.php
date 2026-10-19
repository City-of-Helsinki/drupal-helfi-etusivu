<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\LinkedEvents\DTO;

/**
 * An image style derivative of a Linked Events image.
 */
final readonly class StyledImage {

  /**
   * Constructs a new instance.
   *
   * @param string $url
   *   The image style URL.
   * @param string $lastModifiedTime
   *   The last modified time of the Linked Events image.
   */
  public function __construct(
    public string $url,
    public string $lastModifiedTime,
  ) {
  }

}
