<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Utility\Error;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * A lazy builder for Roadwork data block.
 */
final readonly class LazyBuilder implements TrustedCallbackInterface {

  public function __construct(
    #[Autowire(service: 'logger.channel.helfi_etusivu')]
    private readonly LoggerInterface $logger,
    private readonly VehicleRemovalClient $client,
  ) {
  }

  /**
   * A lazy-builder callback.
   *
   * @param \Drupal\helfi_api_base\ServiceMap\DTO\Address $address
   *   The address.
   * @param string $langcode
   *   The language code.
   * @param int|null $limit
   *   The number of items to show.
   * @param \Drupal\Core\Template\Attribute|null $attributes
   *   Array of attributes to pass to template.
   *
   * @return array
   *   The render array.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function build(Address $address, string $langcode, ?int $limit = NULL, ?Attribute $attributes = NULL): array {
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
      '#theme' => 'helsinki_near_you_lazy_builder_content',
    ];

    try {
      $data = $this->client->getMobileNoteData($address);
    }
    catch (\Exception $e) {
      Error::logException($this->logger, $e);
      return $build + ['#has_error' => TRUE];
    }

    // Foreach ($data->items as $item) {.
    foreach ($data as $item) {
      $build['#content'][] = [
        '#theme' => 'helsinki_near_you_vehicle_removal_item',
        '#title' => $item->title,
        '#address' => $item->address,
        '#date_range' => $item->date_range,
        '#map_url' => $item->map_url,
        '#time_range' => $item->time_range,
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
