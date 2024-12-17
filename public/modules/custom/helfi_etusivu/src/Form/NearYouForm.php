<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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
    $translation_context = 'Helsinki near you form';

    $form['q'] = [
      '#attached' => [
        'drupalSettings' => [
          'helsinki_near_you_form' => [
            'autocompleteRoute' => Url::fromRoute('helfi_etusivu.helsinki_near_you_autocomplete')->tosTring(),
            'minCharAssistiveHint' => $this->t('Type @count or more characters for results', [], ['context' => $translation_context]),
            'inputAssistiveHint' => $this->t(
              'When autocomplete results are available use up and down arrows to review and enter to select. Touch device users, explore by touch or with swipe gestures.',
              [],
              ['context' => $translation_context]
            ),
            'noResultsAssistiveHint' => $this->t('No address suggestions were found', [], ['context' => $translation_context]),
            'someResultsAssistiveHint' => $this->t('There are @count results available.', [], ['context' => $translation_context]),
            'oneResultAssistiveHint' => $this->t('There is one result available.', [], ['context' => $translation_context]),
            'highlightedAssistiveHint' => $this->t(
              '@selectedItem @position of @count is highlighted',
              [],
              ['context' => $translation_context]
            ),
          ],
        ],
      ],
      '#autocomplete_route_name' => 'helfi_etusivu.helsinki_near_you_autocomplete',
      '#placeholder' => $this->t('Eg. Vaasankatu 5', [], ['context' => 'Helsinki near you']),
      '#required' => TRUE,
      '#title' => $this->t('Address'),
      '#label_attributes' => [
        'class' => [
          'hds-text-input__label',
        ],
      ],
      '#type' => 'helfi_etusivu_autocomplete',
      '#wrapper_attributes' => [
        'class' => [
          'hds-text-input',
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
          'helfi-search__submit-button',
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
    $form_state->setRedirect('helfi_etusivu.helsinki_near_you_results', ['q' => urlencode($form_state->getValue('q'))]);
  }

}
