<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedback;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\Distance;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Request;

/**
 * A lazy builder for Feedback block.
 */
final readonly class LazyBuilder implements TrustedCallbackInterface {

  public function __construct(
    private Client $httpClient,
    private PagerManagerInterface $pagerManager,
  ) {
  }

  /**
   * A lazy-builder callback.
   *
   * @param \Drupal\helfi_api_base\ServiceMap\DTO\Address $address
   *   The address.
   * @param string $langcode
   *   The langcode.
   * @param int|null $limit
   *   The number of items to fetch or null.
   * @param \Drupal\Core\Template\Attribute|null $attributes
   *   Array of attributes to pass to template.
   *
   * @return array
   *   The render array.
   */
  public function build(
    Address $address,
    string $langcode,
    ?int $limit,
    ?Attribute $attributes = NULL,
  ): array {
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
      '#theme' => 'helsinki_near_you_lazy_builder_content',
    ];

    $showPager = $limit === NULL;

    // Show 10 items per page if no limit is defined.
    if ($showPager) {
      $limit = 10;
    }

    $data = $this->httpClient
      ->get(new Request(
        lat: $address->location->lat,
        lon: $address->location->lon,
        radius: 2,
        limit: $limit,
        offset: ($this->pagerManager->findPage() * $limit),
        start_date: new DrupalDateTime('-90 days'),
      ));

    foreach ($data->items as $item) {
      $build['#content'][] = [
        '#theme' => 'helsinki_near_you_feedback_item',
        '#status' => $item->status,
        '#description' => $item->description,
        '#uri' => $item->uri,
        '#title' => $item->title,
        '#address' => $item->address,
        '#requested_datetime' => $item->requested_datetime,
        '#feedback_attributes' => $attributes,
        '#distance_label' => Distance::label($item->distance),
      ];
    }

    if ($showPager) {
      if ($data->numItems != 0) {
        $title = new PluralTranslatableMarkup(
          $data->numItems,
          '@count feedback near address @address',
          '@count feedback near address @address',
          ['@address' => $address->streetName->getName($langcode)],
          ['context' => 'Helsinki near you'],
        );
      }
      else {
        $title = new TranslatableMarkup('No feedback was found near address @address', ['@address' => $address->streetName->getName($langcode)], ['context' => 'Helsinki near you'],);
      }

      $build['#title'] = $title;

      $this->pagerManager->createPager($data->numItems, $limit);
      $build['#content']['pager'] = [
        '#type' => 'pager',
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
