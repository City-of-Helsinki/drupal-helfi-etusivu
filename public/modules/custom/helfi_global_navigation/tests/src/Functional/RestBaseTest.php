<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_global_navigation\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;

/**
 * A base test for REST resources.
 */
abstract class RestBaseTest extends BrowserTestBase {

  use UserCreationTrait;
  use JsonApiRequestTestTrait;
  use BasicAuthResourceTestTrait;
  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'serialization',
    'entity',
    'language',
    'json_field',
    'content_translation',
    'basic_auth',
    'rest',
    'helfi_navigation',
    'helfi_global_navigation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The current account.
   *
   * @var \Drupal\user\UserInterface|null
   */
  protected ?UserInterface $account;

  /**
   * The storage.
   *
   * @var \Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage|null
   */
  protected ?GlobalMenuStorage $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->storage = $this->container->get('entity_type.manager')->getStorage('global_menu');
    foreach (['fi', 'sv'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    $this->config('language.negotiation')
      ->set('url.prefixes', ['en' => 'en', 'fi' => 'fi', 'sv' => 'sv'])
      ->save();
    $this->rebuildContainer();
  }

  /**
   * Gets the language object for given langcode.
   *
   * @param string $langcode
   *   The langcode.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The language.
   */
  protected function getLanguage(string $langcode) : LanguageInterface {
    return $this->container->get('language_manager')->getLanguage($langcode);
  }

}
