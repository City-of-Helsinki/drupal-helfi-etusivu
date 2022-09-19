<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_global_navigation\Functional;

use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;

/**
 * Tests installation hooks.
 *
 * @group helfi_global_navigation
 */
class InstallationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'language',
    'content_translation',
    'basic_auth',
    'rest',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Gets the user storage.
   *
   * @return \Drupal\user\UserStorageInterface
   *   The storage.
   */
  private function getUserStorage() : UserStorageInterface {
    return $this->container->get('entity_type.manager')->getStorage('user');
  }

  /**
   * Tests that no user is created when API key is not defined.
   */
  public function testApiKeysNotSet() : void {
    \Drupal::service('module_installer')->install(['helfi_global_navigation']);

    // Make sure no user has menu_api role.
    $accounts = array_filter($this->getUserStorage()->loadMultiple(), function (UserInterface $user) {
      return $user->hasRole('menu_api');
    });

    $this->assertCount(0, $accounts);
  }

  /**
   * Make sure user is created when API key is defined.
   */
  public function testUserCreation() : void {
    $key = base64_encode('helfi-menu:123');
    try {
      $this->config('helfi_navigation.api')->set('key', $key)->save();
    }
    catch (SchemaIncompleteException) {
      // The schema does not exist before module is installed, and the config
      // must be defined before helfi_global_navigation_install() hook.
    }
    \Drupal::service('module_installer')->install(['helfi_global_navigation']);

    $accounts = $this->getUserStorage()->loadByProperties(['name' => 'helfi-menu']);
    $this->assertCount(1, $accounts);

    $account = reset($accounts);
    $account->passRaw = '123';
    $this->drupalLogin($account);
    $this->assertTrue($account->hasRole('menu_api'));
  }

}
