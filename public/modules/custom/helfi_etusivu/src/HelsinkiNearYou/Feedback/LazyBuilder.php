<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedback;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Request;

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
   */
  public function build(
    float $lon,
    float $lat,
  ): array {
    $data = $this->httpClient->get(new Request(
      lat: $lat,
      lon: $lon,
      radius: 0.5,
      locale: 'fi',
    ));
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
      '#type' => 'markup',
      '#markup' => '123',
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() : array {
    return ['build'];
  }

}
