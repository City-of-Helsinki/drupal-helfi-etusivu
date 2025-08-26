<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address;

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
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address $address
   *   The address.
   * @param string $langcode
   *   The langcode.
   * @param int|null $limit
   *   The number of items to fetch or null.
   * @param array $attributes
   *   Array of attributes to pass to template.
   *
   * @return array
   *   The render array.
   */
  public function build(
    Address $address,
    string $langcode,
    ?int $limit,
    array $attributes = [],
  ): array {
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
      '#theme' => 'helsinki_near_you_lazy_builder_content',
    ];

    $data = $this->httpClient
      ->get([
        'dwithin_origin' => sprintf('%f,%f', $address->location->lon, $address->location->lat),
        'dwithin_metres' => 2000,
      ], $langcode, $limit);

    foreach ($data->items as $item) {
      $build['#content'][] = [
        '#theme' => 'helsinki_near_you_event_item',
        '#title' => [
          '#type' => 'link',
          '#title' => $item->title,
          '#url' => $item->uri,
        ],
        '#external_image' => [
          '#theme' => 'imagecache_external_responsive',
          '#uri' => $item->image->url,
          '#responsive_image_style_id' => 'card',
          '#alt' => $item->image->alt,
        ],
        '#object' => $item,
      ];
    }

    $build['#title'] = new TranslatableMarkup('@num results using address @address', [
      '@num' => $data->numItems,
      '@address' => $address->streetName->getName($langcode),
    ], ['context' => 'Helsinki near you']);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() : array {
    return ['build'];
  }

}
