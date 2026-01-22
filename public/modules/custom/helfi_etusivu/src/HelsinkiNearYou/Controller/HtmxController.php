<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\ServiceMapInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A HTMX controller.
 */
abstract class HtmxController extends ControllerBase {

  use AutowireTrait;

  public function __construct(
    protected readonly ServiceMapInterface $serviceMap,
    FormBuilderInterface $formBuilder,
    LanguageManagerInterface $languageManager,
  ) {
    $this->formBuilder = $formBuilder;
    $this->languageManager = $languageManager;
  }

  /**
   * Extracts the address from the given request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\helfi_api_base\ServiceMap\DTO\Address|null
   *   The address or NULL.
   */
  protected function getAddressForQuery(Request $request): ?Address {
    if (!$address = $request->query->get('q')) {
      return NULL;
    }
    return $this->serviceMap->getAddressData(urldecode($address));
  }

  /**
   * The builder callback for HTMX route.
   *
   * @param \Drupal\helfi_api_base\ServiceMap\DTO\Address $address
   *   The address.
   * @param string $langcode
   *   The langcode.
   * @param int|null $limit
   *   The limit.
   *
   * @return array
   *   The render array.
   */
  abstract protected function buildHtmxResults(Address $address, string $langcode, ?int $limit = NULL) : array;

  /**
   * The controller callback for 'htmx' response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   The render array.
   */
  public function htmx(Request $request): array {
    if (!$address = $this->getAddressForQuery($request)) {
      return [];
    }

    $langcode = $this->languageManager
      ->getCurrentLanguage()
      ->getId();

    $limit = (int) $request->query->get('limit') ?: NULL;

    return $this->buildHtmxResults($address, $langcode, $limit);
  }

}
