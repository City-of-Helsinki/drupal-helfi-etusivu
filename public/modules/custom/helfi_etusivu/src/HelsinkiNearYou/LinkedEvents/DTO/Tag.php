<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A DTO to represent Tag.
 */
final readonly class Tag {

  public function __construct(
    public TranslatableMarkup $text,
    public string $attribute,
    public string $value,
  ) {
  }

  /**
   * Converts object to a string.
   *
   * @return string
   *   The value.
   */
  public function __toString(): string {
    return (string) $this->text;
  }

}
