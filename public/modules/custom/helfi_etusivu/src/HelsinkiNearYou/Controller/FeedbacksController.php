<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\FeedbacksSearchForm;

/**
 * A controller to list feedback for given coordinates.
 */
final class FeedbacksController extends SearchPageControllerBase {

  /**
   * {@inheritdoc}
   */
  public function getTitle() : TranslatableMarkup {
    return $this->t('Search feedback near you', [], ['context' => 'Helsinki near you title']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDescription(): TranslatableMarkup {
    return $this->t('Browse feedback near you or search for feedback by location.', [], ['context' => 'Helsinki near you']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSearchForm(): string {
    return FeedbacksSearchForm::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function build(Address $address): array {
    return [
      '#content' => $this->buildFeedback($address->location, 50),
      '#content_attributes' => ['classes' => ['components--helsinki-near-you-feedback-page']],
    ];
  }

}
