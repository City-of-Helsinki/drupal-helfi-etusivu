<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\NewsRss\DTO;

/**
 * A DTO to represent Rss item.
 */
final readonly class RssItem {

  public function __construct(
    public string $title,
    public string $link,
    public string $description,
    public string $pubDate,
    public string $guid,
    public ?RssEnclosure $enclosure = NULL,
  ) {
  }

}
