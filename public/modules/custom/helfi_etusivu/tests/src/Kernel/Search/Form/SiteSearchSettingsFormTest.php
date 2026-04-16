<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Search\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Serialization\Yaml;
use Drupal\KernelTests\KernelTestBase;
use Drupal\helfi_etusivu\Search\Form\SiteSearchSettingsForm;

/**
 * Tests the SiteSearchSettingsForm form.
 *
 * @group helfi_etusivu
 */
class SiteSearchSettingsFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'helfi_etusivu',
    'helfi_api_base',
    'helfi_search',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Tests form submission saves values to config.
   */
  public function testFormSubmission(): void {
    $form_object = SiteSearchSettingsForm::create($this->container);
    $form_state = new FormState();

    // Build and process the form.
    /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
    $form_builder = $this->container->get('form_builder');
    $form_id = $form_builder->getFormId($form_object, $form_state);
    $form = $form_builder->retrieveForm($form_id, $form_state);
    $form_builder->prepareForm($form_id, $form, $form_state);
    $form_builder->processForm($form_id, $form, $form_state);

    // Simulate form submission values.
    $form_state->setValues([
      'jobs' => 'https://www.hel.fi/en/open-jobs',
      'events' => 'https://tapahtumat.hel.fi/en',
      'decisions' => 'https://paatokset.hel.fi/en',
      'contact' => 'https://www.hel.fi/en/contact',
      'ai_register_url' => 'https://www.hel.fi/en/ai-register',
    ]);

    $form_builder->submitForm($form_object, $form_state);

    $config = $this->container->get('config.factory')
      ->get('helfi_etusivu.site_search_settings');

    $this->assertEquals('https://www.hel.fi/en/open-jobs', $config->get('external_links.jobs'));
    $this->assertEquals('https://tapahtumat.hel.fi/en', $config->get('external_links.events'));
    $this->assertEquals('https://paatokset.hel.fi/en', $config->get('external_links.decisions'));
    $this->assertEquals('https://www.hel.fi/en/contact', $config->get('external_links.contact'));
    $this->assertEquals('https://www.hel.fi/en/ai-register', $config->get('ai_register_url'));
  }

  /**
   * Tests that config/install defaults define all required keys.
   */
  public function testConfigDefaults(): void {
    $module_path = $this->container->get('extension.list.module')
      ->getPath('helfi_etusivu');
    $config_file = DRUPAL_ROOT . '/' . $module_path . '/config/install/helfi_etusivu.site_search_settings.yml';

    $this->assertFileExists($config_file);

    $data = Yaml::decode(file_get_contents($config_file));

    $this->assertNotEmpty($data['external_links']['jobs']);
    $this->assertNotEmpty($data['external_links']['events']);
    $this->assertNotEmpty($data['external_links']['decisions']);
    $this->assertNotEmpty($data['external_links']['contact']);
    $this->assertNotEmpty($data['ai_register_url']);
  }

}
