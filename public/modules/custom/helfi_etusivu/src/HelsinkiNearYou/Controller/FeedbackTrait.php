<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Location;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\LazyBuilder;

/**
 * A trait to manage feedback items.
 */
trait FeedbackTrait {

  /**
   * Constructs a render array for feedback items.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Location $location
   *   The location object.
   * @param int|null $limit
   *   The item limit.
   *
   * @return array
   *   The render array.
   */
  protected function buildFeedback(Location $location, ?int $limit = NULL) : array {
    return [
      '#create_placeholder' => TRUE,
      '#lazy_builder_preview' => ['#markup' => ''],
      '#lazy_builder' => [
        LazyBuilder::class . ':build',
        [
          $location,
          // @todo Add date filter back once it works.
          NULL,
          $limit,
        ],
      ],
    ];
  }

}
