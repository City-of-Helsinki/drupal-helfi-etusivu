<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\Enum\InternalSearchLink;
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
    private readonly TranslationInterface $translation,
    private readonly LanguageManagerInterface $languageManager,
  ) {
  }

  /**
   * A lazy-builder callback.
   *
   * @param \Drupal\helfi_api_base\ServiceMap\DTO\Address $address
   *   The address.
   *
   * @return array<mixed>
   *   The render array.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function build(Address $address): array {
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

    $items = [];
    foreach ($data->results as $item) {
      $items[] = [
        '#theme' => 'helsinki_near_you_vehicle_removal_item',
        '#title' => $item->title,
        '#address' => $item->address,
        '#date_range' => $item->date_range,
        '#map_url' => $item->map_url,
        '#time_range' => $item->time_range,
      ];
    }

    $labels = $this->createComponentLabels($data->parkingZone, $data->postalCodes);
    $content = [
      '#theme' => 'helsinki_near_you_vehicle_removal_section',
      '#description' => $labels['description'],
      '#button_url' => $labels['button_url'],
      '#button_label' => $labels['button_label'],
      '#items' => $items,
    ];
    $build['#content'] = $content;

    return $build;
  }

  /**
   * Create the labels for the section.
   *
   * @param string|null $parkingZone
   *   Parking zone name.
   * @param array<string>|null $postalCodes
   *   Postal codes.
   *
   * @return array<mixed>
   *   The labels.
   */
  private function createComponentLabels(?string $parkingZone, ?array $postalCodes): array {
    $queryParameters = '';
    $description = '';

    if ($parkingZone) {
      $description = $this->translation->translate('See vehicle removal requests in your parking zone.', ['context' => 'Helsinki near you']);
      $queryParameters = "?parking_zones=$parkingZone";
    }

    if ($postalCodes) {
      $description = $this->translation->translate('See vehicle removal requests in your postal code area.', ['context' => 'Helsinki near you']);
      foreach ($postalCodes as $key => $postalCode) {
        $queryParameters .= $key == 0 ? '?' : '&';
        $queryParameters .= "postal_code=$postalCode";
      }
    }

    $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    $baseUrl = Url::fromUri(
      InternalSearchLink::VehicleRemoval->getLinkTranslation($langcode),
      ['query' => $queryParameters],
    )->toString();

    return [
      'description' => $description,
      'button_label' => $this->translation->translate('See all vehicle removal requests near you', ['context' => 'Helsinki near you']),
      'button_url' => $baseUrl . $queryParameters,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() : array {
    return ['build'];
  }

}
