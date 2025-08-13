<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

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
   * @param array|null $attributes
   *   Array of attributes to pass to template.
   *
   * @return array
   *   The render array.
   */
  protected function buildFeedback(float $lon, float $lat, ?int $limit = NULL, ?array $attributes = []) : array {
    return [
      '#create_placeholder' => TRUE,
      '#lazy_builder_preview' => ['#markup' => ''],
      '#lazy_builder' => [
        LazyBuilder::class . ':build',
        [
          $lon,
          $lat,
          // @todo Add date filter back once it works.
          NULL,
          $limit,
          $attributes,
        ],
      ],
    ];
  }

}
