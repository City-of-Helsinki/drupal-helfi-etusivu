<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\LlmsTxt;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures the content served at /llms.txt.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'helfi_etusivu_llms_txt_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string>
   */
  protected function getEditableConfigNames(): array {
    return ['helfi_etusivu.llms_txt'];
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Content'),
      '#config_target' => 'helfi_etusivu.llms_txt:content',
      '#rows' => 25,
      '#description' => $this->t('Markdown content served at /llms.txt.'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
