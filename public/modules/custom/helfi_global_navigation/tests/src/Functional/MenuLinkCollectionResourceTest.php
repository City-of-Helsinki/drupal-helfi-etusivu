<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_global_navigation\Functional;

use Drupal\helfi_navigation\ApiManager;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\helfi_navigation\Traits\MenuLinkTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Tests the Global menu rest resource.
 *
 * @group helfi_global_navigation
 */
class MenuLinkCollectionResourceTest extends RestBaseTest {

  use MenuLinkTrait;
  use ApiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path_alias',
    'path',
    'menu_link_content',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->enableTranslation(['menu_link_content']);
  }

  /**
   * Tests GET request without permission.
   */
  public function testGetPermissions() : void {
    $this->createLinks();
    $this->drupalLogin($this->drupalCreateUser([]));

    $this->drupalGet(ApiManager::MENU_ENDPOINT . '/main');
    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response);

    $this->assertSession()->statusCodeEquals(HttpResponse::HTTP_FORBIDDEN);
    $this->assertEquals("The 'restful get helfi_menu_link_collection' permission is required.", $data->message);
  }

  /**
   * Tests GET request with non-existent menu.
   */
  public function test404Response() : void {
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('restful get helfi_menu_link_collection')
      ->save();

    $this->drupalGet(ApiManager::MENU_ENDPOINT . '/non-existent');
    $this->assertSession()->statusCodeEquals(HttpResponse::HTTP_NOT_FOUND);
  }

  /**
   * Tests GET request.
   */
  public function testCollection() : void {
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('restful get helfi_menu_link_collection')
      ->save();

    // Assert that API returns an empty response when menu has
    // no items.
    foreach (['en', 'fi'] as $language) {
      $this->drupalGet(ApiManager::MENU_ENDPOINT . '/main', ['language' => $this->getLanguage($language)]);
      $response = $this->getSession()->getPage()->getContent();
      $data = json_decode($response);
      $this->assertEmpty($data);
    }

    // Create links and make sure caches get flushed.
    $this->createLinks();

    foreach (['en' => 3, 'fi' => 1] as $language => $expectedCount) {
      $this->drupalGet(ApiManager::MENU_ENDPOINT . '/main', ['language' => $this->getLanguage($language)]);
      $response = $this->getSession()->getPage()->getContent();
      $data = json_decode($response);
      $this->assertCount($expectedCount, $data);
      $this->assertCacheTags([
        'config:rest.resource.helfi_menu_link_collection',
        'config:system.menu.main',
      ]);
    }
  }

}
