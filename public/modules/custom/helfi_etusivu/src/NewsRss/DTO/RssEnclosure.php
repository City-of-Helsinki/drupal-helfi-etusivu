<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\NewsRss\DTO;

/**
 * A DTO to represent Rss enclosure.
 */
final readonly class RssEnclosure {

  public function __construct(
    public string $url,
    public int $length,
    public string $type,
  ) {}

}
