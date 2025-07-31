<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\LazyBuilder;

/**
 * A trait to manage feedback items.
 */
trait FeedbackTrait {

  /**
   * Constructs a render array for feedback items.
   *
   * @param float $lon
   *   The longitude.
   * @param float $lat
   *   The latitude.
   * @param int|null $limit
   *   The item limit.
   *
   * @return array
   *   The render array.
   */
  protected function buildFeedback(float $lon, float $lat, ?int $limit = NULL) : array {
    return [
      '#create_placeholder' => TRUE,
      '#lazy_builder_preview' => ['#markup' => ''],
      '#lazy_builder' => [
        LazyBuilder::class . ':build',
        [
          $lon,
          $lat,
          // Don't show feedback older than 6 months.
          new DrupalDateTime('-6 months'),
          $limit,
        ],
      ],
    ];
  }

}
