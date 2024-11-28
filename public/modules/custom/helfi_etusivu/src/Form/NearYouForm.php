<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Search form for near you page.
 */
class NearYouForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'helfi_etusivu_near_you';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'helfi-etusivu-near-you-form';

    $form['q'] = [
      '#autocomplete_route_name' => 'helfi_etusivu.helsinki_near_you_autocomplete',
      '#placeholder' => $this->t('Eg. Vaasankatu 5', [], ['context' => 'Helsinki near you']),
      '#required' => TRUE,
      '#title' => $this->t('Address'),
      '#type' => 'textfield',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('helfi_etusivu.helsinki_near_you_results', ['q' => urlencode($form_state->getValue('q'))]);
  }

}
