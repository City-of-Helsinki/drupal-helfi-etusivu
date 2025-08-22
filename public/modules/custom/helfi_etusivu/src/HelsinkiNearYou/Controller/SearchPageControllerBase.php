<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\ServiceMapInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A controller base class for search pages.
 */
abstract class SearchPageControllerBase extends ControllerBase {

  use LazyBuilderTrait;
  use AutowireTrait;

  public function __construct(
    protected readonly ServiceMapInterface $serviceMap,
    FormBuilderInterface $formBuilder,
    MessengerInterface $messenger,
  ) {
    $this->formBuilder = $formBuilder;
    $this->messenger = $messenger;
  }

  /**
   * A title callback for given controller.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated route title.
   */
  abstract public function getTitle() : TranslatableMarkup;

  /**
   * A callback for '#component_description'.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description.
   */
  abstract protected function getDescription() : TranslatableMarkup;

  /**
   * The form used in '#autosuggest_form'.
   *
   * @return string
   *   The form class.
   */
  abstract protected function getSearchForm() : string;

  /**
   * The render array callback for ::content().
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address $address
   *   The address.
   *
   * @return array
   *   The render array.
   */
  abstract protected function build(Address $address) : array;

  /**
   * A controller callback for feedback route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array[]
   *   The render array.
   */
  public function content(Request $request) : array {
    $build = [
      '#theme' => 'helsinki_near_you_search_page',
      '#cache' => [
        'contexts' => ['url.query_args:q', 'url.query_args:page'],
        'max-age' => 0,
      ],
      '#component_title' => $this->getTitle(),
      '#component_description' => $this->getDescription(),
      '#autosuggest_form' => $this->formBuilder
        ->getForm($this->getSearchForm()),
    ];

    $address = $request->query->get('q', '');
    $addressData = $this->serviceMap->getAddressData(urldecode($address));

    if (!$addressData) {
      $this->messenger()->addError(
        $this->t(
          'Make sure the address is written correctly. You can also search using a nearby street number.',
          [],
          ['context' => 'React search: Address not found hint']
        )
      );

      return $build;
    }
    $build += $this->build($addressData);

    return $build;
  }

}
