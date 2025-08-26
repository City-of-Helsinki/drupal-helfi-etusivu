<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO;

/**
 * A DTO to represent image.
 */
final readonly class Image {

  public function __construct(
    public string $url,
    public string $alt,
    public string $photographer,
  ) {
  }

  /**
   * Constructs a new instance from given array.
   *
   * @param array $data
   *   The data.
   *
   * @return self
   *   The self or null.
   */
  public static function createFromArray(?array $data): self {
    return new self(
      $data['url'],
      $data['alt_text'] ?? '',
      $data['photographer_name'] ?? '',
    );
  }

}
