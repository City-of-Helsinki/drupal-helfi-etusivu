<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\NewsRss\DTO;

/**
 * A DTO to represent RSS feed.
 */
final readonly class RssFeed {

  public function __construct(
    public string $title,
    public string $link,
    public string $language,
    public string $description,
    public array $items = [],
  ) {
  }

}
