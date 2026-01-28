<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\ServiceMapInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\RoadworkSearchForm;
use Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\LazyBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the Helsinki Near You Roadworks page.
 *
 * This controller handles the display of roadwork projects near a given
 * location.
 * It provides methods for:
 * - Displaying roadwork projects near a user-specified address
 * - Handling address-based searches
 * - Formatting project data for display in the Helsinki Design System
 * - Managing error states and user feedback.
 *
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataServiceInterface
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData\RoadworkDataClientInterface
 */
final class RoadworksController extends SearchPageControllerBase {

  public function __construct(
    private LazyBuilder $lazyBuilder,
    ServiceMapInterface $serviceMap,
    FormBuilderInterface $formBuilder,
    LanguageManagerInterface $languageManager,
  ) {
    parent::__construct($serviceMap, $formBuilder, $languageManager);
  }

  /**
   * A controller callback for roadworks route that provides the route title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated route title.
   */
  public function getTitle() : TranslatableMarkup {
    return $this->t('Search for street and park projects', [], ['context' => 'Helsinki near you roadworks search']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDescription(): TranslatableMarkup {
    return $this->t('Search for street and park projects by entering an address. The search will show the projects that are within two kilometres of the address you enter.', [], ['context' => 'Helsinki near you roadworks search']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSearchForm(): string {
    return RoadworkSearchForm::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildFormResults(Address $address, string $langcode, Request $request): array {
    return [
      '#content' => $this->buildRoadworksHtmxContainer($request),
      '#content_attributes' => ['classes' => ['components--helsinki-near-you-roadwork-page']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildHtmxResults(Address $address, string $langcode, ?int $limit = NULL): array {
    return $this->lazyBuilder->build($address, $langcode, $limit);
  }

}
