<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_global_navigation\Kernel;

use Drupal\helfi_navigation\ApiManager;
use Drupal\Tests\helfi_navigation\Kernel\MenuTreeBuilderTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Tests the Global menu rest resource.
 *
 * @group helfi_global_navigation
 */
class MenuLinkCollectionResourceTest extends MenuTreeBuilderTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_global_navigation',
    'serialization',
    'migrate',
    'json_field',
    'entity',
    'rest',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('global_menu');
    $this->installConfig(['helfi_global_navigation']);
  }

  /**
   * Tests GET request without permission.
   */
  public function testGetPermissions() : void {
    $this->createLinks();
    $this->drupalSetUpCurrentUser();

    $request = $this->getMockedRequest(ApiManager::MENU_ENDPOINT . '/main');
    $response = $this->processRequest($request);
    $data = \GuzzleHttp\json_decode($response->getContent());

    $this->assertEquals(HttpResponse::HTTP_FORBIDDEN, $response->getStatusCode());
    $this->assertEquals("The 'restful get helfi_menu_link_collection' permission is required.", $data->message);
  }

  /**
   * Tests GET request with non-existent menu.
   */
  public function test404Response() : void {
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('restful get helfi_menu_link_collection')
      ->save();
    $this->drupalSetCurrentUser(User::load(0));

    $request = $this->getMockedRequest(ApiManager::MENU_ENDPOINT . '/non-existent');
    $response = $this->processRequest($request);
    $this->assertEquals(HttpResponse::HTTP_NOT_FOUND, $response->getStatusCode());
  }

  /**
   * Tests GET request.
   */
  public function testCollection() : void {
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('restful get helfi_menu_link_collection')
      ->save();
    $this->drupalSetCurrentUser(User::load(0));

    // Assert that API returns an empty response when menu has
    // no items.
    foreach (['en', 'fi'] as $language) {
      $request = $this->getMockedRequest('/' . $language . ApiManager::MENU_ENDPOINT . '/main');
      $response = $this->processRequest($request);
      $data = \GuzzleHttp\json_decode($response->getContent());
      $this->assertEmpty($data);
    }

    $this->createLinks();

    foreach (['en' => 3, 'fi' => 1] as $language => $expectedCount) {
      $request = $this->getMockedRequest('/' . $language . ApiManager::MENU_ENDPOINT . '/main');
      $response = $this->processRequest($request);
      $data = \GuzzleHttp\json_decode($response->getContent());
      $this->assertNotEmpty($data);
      $this->assertCount($expectedCount, $data);
    }
  }

}
