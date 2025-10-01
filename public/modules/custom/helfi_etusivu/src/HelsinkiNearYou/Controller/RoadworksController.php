<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
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
  protected function build(Address $address): array {
    $langcode = $this->languageManager
      ->getCurrentLanguage()
      ->getId();

    return [
      '#content' => $this->buildRoadworks($address, $langcode, NULL, new Attribute(['title_level' => ['h4']])),
      '#content_attributes' => ['classes' => ['components--helsinki-near-you-roadwork-page']],
    ];
  }

}
