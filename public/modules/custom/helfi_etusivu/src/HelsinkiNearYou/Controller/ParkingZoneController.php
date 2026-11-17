<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\ServiceMapInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\ParkingZone\LazyBuilder;

/**
 * A controller to show the resident parking zone for given address.
 */
final class ParkingZoneController extends HtmxController {

  public function __construct(
    private readonly LazyBuilder $lazyBuilder,
    ServiceMapInterface $serviceMap,
    FormBuilderInterface $formBuilder,
    LanguageManagerInterface $languageManager,
  ) {
    parent::__construct($serviceMap, $formBuilder, $languageManager);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildHtmxResults(Address $address, string $langcode, ?int $limit = NULL): array {
    return $this->lazyBuilder->build($address);
  }

}
