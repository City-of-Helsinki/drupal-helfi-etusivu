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
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('helfi_etusivu.site_search_settings');

    $form['external_links'] = [
      '#type' => 'details',
      '#title' => $this->t('External links'),
      '#open' => TRUE,
    ];

    $form['external_links']['jobs'] = [
      '#type' => 'url',
      '#title' => $this->t('Open jobs URL'),
      '#default_value' => $config->get('external_links.jobs'),
    ];

    $form['external_links']['events'] = [
      '#type' => 'url',
      '#title' => $this->t('Events URL'),
      '#default_value' => $config->get('external_links.events'),
    ];

    $form['external_links']['decisions'] = [
      '#type' => 'url',
      '#title' => $this->t('Decisions URL'),
      '#default_value' => $config->get('external_links.decisions'),
    ];

    $form['external_links']['contact'] = [
      '#type' => 'url',
      '#title' => $this->t('Contact URL'),
      '#default_value' => $config->get('external_links.contact'),
    ];

    $form['ai_register_url'] = [
      '#type' => 'url',
      '#title' => $this->t('AI register URL'),
      '#default_value' => $config->get('ai_register_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
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
