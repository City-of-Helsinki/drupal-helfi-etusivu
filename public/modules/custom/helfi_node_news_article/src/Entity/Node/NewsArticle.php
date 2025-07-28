<?php

declare(strict_types=1);

namespace Drupal\helfi_node_news_article\Entity\Node;

use Drupal\helfi_etusivu\NewsTermsTrait;
use Drupal\node\Entity\Node;

/**
 * A bundle class for News article -node.
 */
final class NewsArticle extends Node {

  use NewsTermsTrait;

  /**
   * Get human-readable "published at" time of the News article.
   *
   * @return string
   *   The human-readable "published at" time.
   */
  public function getPublishedHumanReadable(): string {
    $published = $this->get('published_at')->value ?? $this->getCreatedTime();
    return \Drupal::service('date.formatter')->format((int) $published, 'publication_date_format');
  }

  /**
   * Get machine-readable "published at" time of the News article.
   *
   * @return string
   *   The machine-readable "published at" time.
   */
  public function getPublishedMachineReadable(): string {
    $published = $this->get('published_at')->value ?? $this->getCreatedTime();
    return \Drupal::service('date.formatter')->format((int) $published, 'custom', 'Y-m-d\TH:i');
  }

  /**
   * Get human-readable "updated" time of the News article.
   *
   * @return string
   *   The human-readable "updated at" time.
   */
  public function getUpdatedHumanReadable(): string {
    return \Drupal::service('date.formatter')->format($this->get('changed')->value, 'publication_date_format');
  }

  /**
   * Get machine-readable "updated" time of the News article.
   *
   * @return string
   *   The machine-readabe "updated" time.
   */
  public function getUpdatedMachineReadable(): string {
    return \Drupal::service('date.formatter')->format($this->get('changed')->value, 'custom', 'Y-m-d\TH:i');
  }

}
