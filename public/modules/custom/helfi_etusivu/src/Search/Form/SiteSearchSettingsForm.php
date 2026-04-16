<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Site search settings form.
 */
final class SiteSearchSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   *
   * @return array<string>
   *   The editable config names.
   */
  protected function getEditableConfigNames(): array {
    return ['helfi_etusivu.site_search_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'helfi_etusivu_site_search_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array<string, mixed>
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('helfi_etusivu.site_search_settings');

    $form['external_links'] = [
      '#type' => 'details',
      '#title' => $this->t('External links'),
      '#open' => TRUE,
    ];

    $external_link_fields = [
      'jobs' => $this->t('Open jobs URL'),
      'events' => $this->t('Events URL'),
      'decisions' => $this->t('Decisions URL'),
      'contact' => $this->t('Contact URL'),
    ];

    foreach ($external_link_fields as $key => $label) {
      $form['external_links'][$key] = [
        '#type' => 'url',
        '#title' => $label,
        '#default_value' => $config->get("external_links.{$key}"),
      ];
    }

    $form['ai_register_url'] = [
      '#type' => 'url',
      '#title' => $this->t('AI register URL'),
      '#default_value' => $config->get('ai_register_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('helfi_etusivu.site_search_settings')
      ->set('external_links', [
        'jobs' => $form_state->getValue('jobs'),
        'events' => $form_state->getValue('events'),
        'decisions' => $form_state->getValue('decisions'),
        'contact' => $form_state->getValue('contact'),
      ])
      ->set('ai_register_url', $form_state->getValue('ai_register_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
