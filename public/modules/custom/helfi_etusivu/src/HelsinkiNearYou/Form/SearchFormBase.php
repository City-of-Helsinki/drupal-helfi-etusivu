<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Search form for near you page.
 */
abstract class SearchFormBase extends FormBase {

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
    $form['#attributes']['class'][] = 'hdbt-search__form helfi-etusivu-near-you-form';
    $form['q'] = [
      '#placeholder' => $this->t('For example, Kotikatu 1', [], ['context' => 'Helsinki near you']),
      '#required' => TRUE,
      '#title' => $this->t('Home address', [], ['context' => 'Helsinki near you']),
      '#default_value' => $this->getRequest()?->query->get('q', ''),
      '#type' => 'helfi_location_autocomplete',
      '#autocomplete_route_name' => 'helfi_api_base.location_autocomplete',
      '#wrapper_attributes' => [
        'class' => [
          'helfi-etusivu-near-you-form__address-input',
        ],
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->getRedirectRoute(), ['q' => $form_state->getValue('q')]);
  }

}
