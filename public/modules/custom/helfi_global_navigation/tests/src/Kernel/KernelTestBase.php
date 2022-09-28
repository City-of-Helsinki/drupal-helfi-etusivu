<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_global_navigation\Kernel;

use Drupal\Tests\helfi_api_base\Kernel\ApiKernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\LanguageManagerTrait;

/**
 * A base class for global menu tests.
 */
abstract class KernelTestBase extends ApiKernelTestBase {

  use LanguageManagerTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'serialization',
    'migrate',
    'entity',
    'rest',
    'helfi_language_negotiator_test',
    'language',
    'json_field',
    'content_translation',
    'helfi_navigation',
    'helfi_global_navigation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('global_menu');
    $this->installConfig('helfi_global_navigation');

    $this->installConfig(['content_translation']);
    $this->setupLanguages();

    $this->config('language.negotiation')
      ->set('url.prefixes', ['en' => 'en', 'fi' => 'fi', 'sv' => 'sv'])
      ->save();

    \Drupal::service('kernel')->rebuildContainer();
  }

}
