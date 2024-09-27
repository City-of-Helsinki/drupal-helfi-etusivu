<?php

declare(strict_types=1);

namespace Drupal\helfi_node_news_article\Entity\Node;

use Drupal\helfi_annif\RecommendableBase;

/**
 * A bundle class for News article -node.
 */
final class NewsArticle extends RecommendableBase {

  /**
   * Get human-readable "published at" time of the News article.
   *
   * @return string
   *   The human-readable "published at" time.
   */
  public function getPublishedHumanReadable(): string {
    return \Drupal::service('date.formatter')->format($this->get('published_at')->value, 'publication_date_format');
  }

  /**
   * Get machine-readable "published at" time of the News article.
   *
   * @return string
   *   The machine-readabe "published at" time.
   */
  public function getPublishedMachineReadable(): string {
    return \Drupal::service('date.formatter')->format($this->get('published_at')->value, 'custom', 'Y-m-d\TH:i');
  }

  /**
   * Get human-readable "updated" time of the News article.
   *
   * @return string
   *   The human-readable "updated at" time.
   */
  public function getUpdatedHumanReadable(): string {
    return \Drupal::service('date.formatter')->format($this->get('created')->value, 'publication_date_format');
  }

  /**
   * Get machine-readable "updated" time of the News article.
   *
   * @return string
   *   The machine-readabe "updated" time.
   */
  public function getUpdatedMachineReadable(): string {
    return \Drupal::service('date.formatter')->format($this->get('created')->value, 'custom', 'Y-m-d\TH:i');
  }

}
