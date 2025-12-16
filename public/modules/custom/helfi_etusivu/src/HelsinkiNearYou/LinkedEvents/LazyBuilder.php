<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO\TargetGroup;

/**
 * A lazy builder for Feedback block.
 */
final readonly class LazyBuilder implements TrustedCallbackInterface {

  public function __construct(
    private Client $httpClient,
  ) {
  }

  /**
   * A lazy-builder callback.
   *
   * @param \Drupal\helfi_api_base\ServiceMap\DTO\Address $address
   *   The address.
   * @param string $langcode
   *   The langcode.
   * @param int $limit
   *   The number of items to fetch or null.
   *
   * @return array
   *   The render array.
   */
  public function build(
    Address $address,
    string $langcode,
    int $limit,
  ): array {
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
      '#theme' => 'helsinki_near_you_lazy_builder_content',
    ];

    // Search with Adult target group by default.
    $query = array_merge([
      'dwithin_origin' => sprintf('%f,%f', $address->location->lon, $address->location->lat),
      'dwithin_metres' => 2000,
    ], TargetGroup::Adult->getQueryFilters());

    $data = $this->httpClient
      ->get($query, $langcode, $limit);

    foreach ($data->items as $item) {
      $element = [
        '#theme' => 'helsinki_near_you_event_item',
        '#title' => [
          '#type' => 'link',
          '#title' => $item->title,
          '#url' => $item->uri,
        ],
        '#object' => $item,
      ];

      if ($item->image) {
        $element['#external_image'] = [
          '#theme' => 'imagecache_external_responsive',
          '#uri' => $item->image->url,
          '#responsive_image_style_id' => 'card',
          '#alt' => $item->image->alt,
        ];
      }
      $build['#content'][] = $element;
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
