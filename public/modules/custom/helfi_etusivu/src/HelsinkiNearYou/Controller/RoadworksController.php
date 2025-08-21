<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\RoadworkSearchForm;

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

  /**
   * A controller callback for roadworks route that provides the route title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated route title.
   */
  public function getTitle() : TranslatableMarkup {
    return $this->t('Find roadworks near you', [], ['context' => 'Helsinki near you roadworks search']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDescription(): TranslatableMarkup {
    return $this->t('Browse roadworks near you or search for roadworks by location. The search shows results within 1 kilometer of the address.', [], ['context' => 'Helsinki near you roadworks search']);
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
  protected function build(Address $address): array {
    return [
      '#content' => $this->buildRoadworks($address),
      '#content_attributes' => ['classes' => ['components--helsinki-near-you-roadwork-page']],
    ];
  }

}
