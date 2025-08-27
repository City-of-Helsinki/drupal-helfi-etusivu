<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Template\Attribute;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedbacks\LazyBuilder;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\LazyBuilder as EventsLazyBuilder;
use Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\LazyBuilder as RoadWorkLazyBuilder;

/**
 * A trait to manage lazy builder items.
 */
trait LazyBuilderTrait {

  /**
   * Constructs a lazy builder preview render array.
   *
   * @return array
   *   The render array.
   */
  private function getLazyBuilderPreview(?int $limit = NULL) : array {
    return [
      '#theme' => 'helsinki_near_you_lazy_builder_preview',
      '#num_items' => $limit,
    ];
  }

  /**
   * Constructs a render array for events.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address $address
   *   The address.
   * @param string $langcode
   *   The language code.
   * @param int $limit
   *   The number of items to show.
   *
   * @return array
   *   The events render array.
   */
  protected function buildEvents(Address $address, string $langcode, int $limit) : array {
    return [
      '#create_placeholder' => TRUE,
      '#lazy_builder_preview' => $this->getLazyBuilderPreview($limit),
      '#lazy_builder' => [
        EventsLazyBuilder::class . ':build',
        [
          $address,
          $langcode,
          $limit,
        ],
      ],
    ];
  }

  /**
   * Constructs a render array for feedback items.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address $address
   *   The address.
   * @param string $langcode
   *   The langcode.
   * @param int|null $limit
   *   The item limit.
   * @param \Drupal\Core\Template\Attribute|null $attributes
   *   The attributes.
   *
   * @return array
   *   The render array.
   */
  protected function buildFeedback(Address $address, string $langcode, ?int $limit = NULL, ?Attribute $attributes = NULL) : array {
    return [
      '#create_placeholder' => TRUE,
      '#lazy_builder_preview' => $this->getLazyBuilderPreview($limit),
      '#lazy_builder' => [
        LazyBuilder::class . ':build',
        [
          $address,
          $langcode,
          $limit,
          $attributes,
        ],
      ],
    ];
  }

  /**
   * Constructs a render array for roadwork items.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address $address
   *   The address.
   * @param string $langcode
   *   The langcode.
   * @param int|null $limit
   *   The number of items to show.
   * @param \Drupal\Core\Template\Attribute|null $attributes
   *   Array of attributes to pass to template.
   *
   * @return array
   *   The render array.
   */
  protected function buildRoadworks(Address $address, string $langcode, ?int $limit = NULL, ?Attribute $attributes = NULL) : array {
    return [
      '#create_placeholder' => TRUE,
      '#lazy_builder_preview' => $this->getLazyBuilderPreview($limit),
      '#lazy_builder' => [
        RoadWorkLazyBuilder::class . ':build',
        [
          $address,
          $langcode,
          $limit,
          $attributes,
        ],
      ],
    ];
  }

}
