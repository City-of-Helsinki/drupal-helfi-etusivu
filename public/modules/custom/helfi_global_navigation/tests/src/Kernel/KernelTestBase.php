<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_global_navigation\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\helfi_api_base\Kernel\ApiKernelTestBase;

/**
 * A base class for global menu tests.
 */
abstract class KernelTestBase extends ApiKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'serialization',
    'migrate',
    'entity',
    'rest',
    'language',
    'json_field',
    'content_translation',
    'helfi_global_navigation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('global_menu');
    $this->installConfig('helfi_global_navigation');
    $this->installConfig(['language', 'content_translation']);

    foreach (['fi', 'sv'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    $this->config('language.negotiation')
      ->set('url.prefixes', ['en' => 'en', 'fi' => 'fi', 'sv' => 'sv'])
      ->save();

    \Drupal::service('kernel')->rebuildContainer();
  }

}
