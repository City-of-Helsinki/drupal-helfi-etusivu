<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\ServiceMapInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\LazyBuilder;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\FeedbackSearchForm;
use Symfony\Component\HttpFoundation\Request;

/**
 * A controller to list feedback for given coordinates.
 */
final class FeedbackController extends SearchPageControllerBase {

  public function __construct(
    private LazyBuilder $lazyBuilder,
    ServiceMapInterface $serviceMap,
    FormBuilderInterface $formBuilder,
    LanguageManagerInterface $languageManager,
  ) {
    parent::__construct($serviceMap, $formBuilder, $languageManager);
  }

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
  public function buildHtmxResults(Address $address, string $langcode, ?int $limit = NULL): array {
    return $this->lazyBuilder->build($address, $langcode, $limit);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildFormResults(Address $address, string $langcode, Request $request): array {
    return [
      '#content' => $this->buildFeedbackHtmxContainer($request),
      '#content_attributes' => ['classes' => ['components--helsinki-near-you-feedback-page']],
    ];
  }

}
