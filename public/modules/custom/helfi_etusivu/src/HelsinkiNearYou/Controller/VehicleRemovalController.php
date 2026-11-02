<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\ServiceMapInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\VehicleRemovalSearchForm;
use Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval\LazyBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the Helsinki Near You Vehicle Removal.
 */
final class VehicleRemovalController extends SearchPageControllerBase {

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
  public function getTitle() : TranslatableMarkup {
    // @todo Todo.
    return $this->t('placeholder', [], ['context' => 'Helsinki near you title']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDescription(): TranslatableMarkup {
    // @todo Todo.
    return $this->t('placeholder', [], ['context' => 'Helsinki near you']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSearchForm(): string {
    return VehicleRemovalSearchForm::class;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<mixed>
   */
  public function buildHtmxResults(Address $address, string $langcode, ?int $limit = NULL): array {
    return $this->lazyBuilder->build($address);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<mixed>
   */
  protected function buildFormResults(Address $address, string $langcode, Request $request): array {
    return [];
  }

}
