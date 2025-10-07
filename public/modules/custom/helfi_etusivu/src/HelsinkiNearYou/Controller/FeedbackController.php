<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\FeedbackSearchForm;

/**
 * A controller to list feedback for given coordinates.
 */
final class FeedbackController extends SearchPageControllerBase {

  /**
   * {@inheritdoc}
   */
  public function getTitle() : TranslatableMarkup {
    return $this->t('Find feedback', [], ['context' => 'Helsinki near you title']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDescription(): TranslatableMarkup {
    return $this->t('The search shows results within two kilometers from the address and from the past 90 days.', [], ['context' => 'Helsinki near you']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSearchForm(): string {
    return FeedbackSearchForm::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function build(Address $address): array {
    $langcode = $this->languageManager
      ->getCurrentLanguage()
      ->getId();

    return [
      '#content' => $this->buildFeedback($address, $langcode, NULL, new Attribute(['title_level' => ['h4']])),
      '#content_attributes' => ['classes' => ['components--helsinki-near-you-feedback-page']],
    ];
  }

}
