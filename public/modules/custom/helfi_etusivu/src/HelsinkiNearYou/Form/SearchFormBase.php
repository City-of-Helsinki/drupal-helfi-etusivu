<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\helfi_api_base\ServiceMap\ServiceMapInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Search form for near you page.
 */
abstract class SearchFormBase extends FormBase {

  public function __construct(
    protected readonly ServiceMapInterface $serviceMap,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : static {
    return new static(
      $container->get(ServiceMapInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'helfi_etusivu_near_you';
  }

  /**
   * Gets the redirect route.
   *
   * @return string
   *   The route.
   */
  abstract protected function getRedirectRoute() : string;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) : array {
    $form['#attributes'] = [
      'class' => ['hdbt-search__form helfi-etusivu-near-you-form'],
      'novalidate' => TRUE,
    ];
    $form['home_address'] = [
      '#placeholder' => $this->t('For example, Kotikatu 1', [], ['context' => 'Helsinki near you']),
      '#title' => $this->t('Address', [], ['context' => 'Helsinki near you']),
      '#default_value' => $this->getRequest()?->query->get('home_address', ''),
      '#type' => 'helfi_location_autocomplete',
      '#autocomplete_route_name' => 'helfi_api_base.location_autocomplete',
      '#wrapper_attributes' => [
        'class' => [
          'helfi-etusivu-near-you-form__address-input',
        ],
      ],
      '#attributes' => [
        'aria-describedby' => 'js-address-not-found-error js-address-mandatory-error js-locate-error',
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#attributes' => [
        'class' => [
          'hds-button',
          'hds-button--primary',
          'hdbt__form-submit',
        ],
      ],
      '#type' => 'submit',
      '#value' => $this->t('Search', [], ['context' => 'Helsinki near you']),
    ];

    return $form;
  }

  /**
   * Validates the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) : void {
    parent::validateForm($form, $form_state);

    $address = $form_state->getValue('home_address');

    if (!$address) {
      $form_state->setErrorByName(
       'home_address',
       $this->t('Address is required.', [], ['context' => 'Helsinki near you']),
      );
      return;
    }

    if ($address && !$this->serviceMap->getAddressData(urldecode($address))) {
      $form_state->setErrorByName(
        'home_address',
        [
          $this->t('No results were found for the address', [], ['context' => 'Helsinki near you']),
          $this->t(
          'Make sure the address is correct. You can also try searching with a nearby address. The search suggests addresses as you type.',
          [],
          ['context' => 'Address search error message']
          ),
        ]
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->getRedirectRoute(), ['home_address' => $form_state->getValue('home_address')]);
  }

}
