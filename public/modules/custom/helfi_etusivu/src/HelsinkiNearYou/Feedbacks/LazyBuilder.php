<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Location;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\DTO\Request;

/**
 * A lazy builder for Feedback block.
 */
final readonly class LazyBuilder implements TrustedCallbackInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\Client $httpClient
   *   The http client.
   */
  public function __construct(
    private Client $httpClient,
  ) {
  }

  /**
   * A lazy-builder callback.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Location $location
   *   The location.
   * @param \Drupal\Core\Datetime\DrupalDateTime|null $start_date
   *   The start date or null.
   * @param int|null $limit
   *   The number of items to fetch or null.
   * @param array $attributes
   *   Array of attributes to pass to template.
   *
   * @return array
   *   The render array.
   */
  public function build(
    Location $location,
    ?DrupalDateTime $start_date,
    ?int $limit,
    array $attributes = [],
  ): array {
    $data = $this->httpClient
      ->get(new Request(
        lat: $location->lat,
        lon: $location->lon,
        radius: 0.5,
        limit: $limit,
        start_date: $start_date,
      ));

    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    foreach ($data as $item) {
      $build['items'][] = [
        '#theme' => 'helsinki_near_you_feedback_item',
        '#status' => $item->status,
        '#description' => $item->description,
        '#uri' => $item->uri,
        '#title' => $item->title,
        '#address' => $item->address,
        '#requested_datetime' => $item->requested_datetime,
        '#feedback_attributes' => $attributes,
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() : array {
    return ['build'];
  }

}
