<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_global_navigation\Functional;

use Drupal\Core\Url;
use Drupal\helfi_global_navigation\Entity\GlobalMenu;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Tests the Global menu rest resource.
 *
 * @group helfi_global_navigation
 */
class GlobalMenuResourceTest extends RestBaseTest {

  /**
   * Gets the mocked JSON.
   *
   * @return array[]
   *   The mock data.
   */
  private function getMockJson() : array {
    return [
      'fi' => [
        'site_name' => 'Liikenne fi',
        'status' => TRUE,
        'menu_tree' => [
          'name' => 'Kaupunkiympäristö',
          'id' => 'base:liikenne',
          'external' => FALSE,
          'hasItems' => TRUE,
          'weight' => 0,
          'sub_tree' => [
            [
              'id' => 'menu_link_content:7c9ddcc2-4d07-4785-8940-046b4cb85fb4',
              'name' => 'Depth 2 fi',
              'parentId' => 'base:liikenne',
              'url' => 'https://localhost/fi/2',
              'external' => FALSE,
              'hasItems' => TRUE,
              'weight' => 0,
              'sub_tree' => [
                [
                  'id' => 'menu_link_content:de6409aa-c620-4327-90f4-127176f209b2',
                  'name' => 'Depth 3 fi',
                  'parentId' => 'menu_link_content:7c9ddcc2-4d07-4785-8940-046b4cb85fb4',
                  'url' => 'https://localhost/fi/3',
                  'external' => FALSE,
                  'hasItems' => TRUE,
                  'weight' => 0,
                  'sub_tree' => [
                    [
                      'id' => 'menu_link_content:8a28fc72-b10e-49f1-860f-e0967ef2fe0a',
                      'name' => 'Depth 4 fi',
                      'parentId' => 'menu_link_content:de6409aa-c620-4327-90f4-127176f209b2',
                      'url' => 'https://localhost/fi/4',
                      'external' => FALSE,
                      'hasItems' => FALSE,
                      'weight' => 0,
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
      'en' => [
        'site_name' => 'Liikenne en',
        'menu_tree' => [
          'name' => 'Kaupunkiympäristö en',
          'id' => 'base:liikenne',
          'external' => FALSE,
          'hasItems' => TRUE,
          'weight' => 0,
          'sub_tree' => [
            [
              'id' => 'menu_link_content:7c9ddcc2-4d07-4785-8940-046b4cb85fb4',
              'name' => 'Depth 2 en',
              'parentId' => 'base:liikenne',
              'url' => 'https://localhost/en/2',
              'external' => FALSE,
              'hasItems' => TRUE,
              'weight' => 0,
              'sub_tree' => [
                [
                  'id' => 'menu_link_content:de6409aa-c620-4327-90f4-127176f209b2',
                  'name' => 'Depth 3 en',
                  'parentId' => 'menu_link_content:7c9ddcc2-4d07-4785-8940-046b4cb85fb4',
                  'url' => 'https://localhost/en/3',
                  'external' => FALSE,
                  'hasItems' => TRUE,
                  'weight' => 0,
                  'sub_tree' => [
                    [
                      'id' => 'menu_link_content:8a28fc72-b10e-49f1-860f-e0967ef2fe0a',
                      'name' => 'Depth 4 en',
                      'parentId' => 'menu_link_content:de6409aa-c620-4327-90f4-127176f209b2',
                      'url' => 'https://localhost/en/4',
                      'external' => FALSE,
                      'hasItems' => FALSE,
                      'weight' => 0,
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Creates a new global menu entity.
   *
   * @param string $id
   *   The id.
   * @param string $projectName
   *   The project name.
   * @param array $menu
   *   The menu.
   *
   * @return \Drupal\helfi_global_navigation\Entity\GlobalMenu
   *   The global menu entity.
   */
  private function createGlobalMenu(string $id, string $projectName, array $menu = []) : GlobalMenu {
    $entity = $this->storage
      ->createById($id)
      ->set('langcode', 'en')
      ->setLabel($projectName);

    if ($menu) {
      $entity->setMenuTree(json_encode($menu));
    }
    $entity->save();
    return $entity;
  }

  /**
   * Tests GET request without permission.
   */
  public function testGetPermissions() : void {
    $this->drupalLogin($this->setUpCurrentUser());
    $this->createGlobalMenu('liikenne', 'Liikenne');

    // Test individual entity.
    $this->drupalGet('/api/v1/global-menu/liikenne');
    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response);
    $this->assertSession()->statusCodeEquals(HttpResponse::HTTP_FORBIDDEN);
    $this->assertEquals('You are not authorized to view this global_menu entity', $data->message);

    $this->drupalGet('/api/v1/global-menu');
    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response);
    $this->assertSession()->statusCodeEquals(HttpResponse::HTTP_FORBIDDEN);
    $this->assertEquals("The 'restful get helfi_global_menu_collection' permission is required.", $data->message);
  }

  /**
   * Tests GET method.
   */
  public function testGetRoutes() : void {
    $mock = $this->getMockJson();
    $entity = $this->createGlobalMenu('liikenne', 'Liikenne en', $mock['en']['menu_tree']);
    $translation = $entity->addTranslation('fi')
      ->setMenuTree(json_encode($mock['fi']['menu_tree']))
      ->setLabel('Liikenne fi');
    $translation->save();

    // Visit pages as anonymous user to make sure everything is cached per
    // permissions.
    foreach (['en', 'fi'] as $langcode) {
      $this->drupalGet('/api/v1/global-menu', ['language' => $this->getLanguage($langcode)]);
      $this->assertSession()->statusCodeEquals(HttpResponse::HTTP_UNAUTHORIZED);

      $this->drupalGet('/api/v1/global-menu/liikenne', ['language' => $this->getLanguage($langcode)]);
      $this->assertSession()->statusCodeEquals(HttpResponse::HTTP_UNAUTHORIZED);
    }

    $user = $this->createUser(permissions:  [
      'restful get helfi_global_menu_collection',
      'view global_menu',
    ]);
    $this->drupalLogin($user);

    foreach (['en', 'fi'] as $langcode) {
      $this->drupalGet('/api/v1/global-menu', ['language' => $this->getLanguage($langcode)]);
      $response = $this->getSession()->getPage()->getContent();
      $collectionData = json_decode($response);
      $this->assertCacheTags([
        'config:rest.resource.helfi_global_menu_collection',
        'global_menu:liikenne',
      ]);
      $this->assertCacheContext('url.query_args');
      $this->assertSession()->statusCodeEquals(HttpResponse::HTTP_OK);

      $this->assertEquals('liikenne', $collectionData->liikenne->project[0]->value);
      $this->assertEquals('Liikenne ' . $langcode, $collectionData->liikenne->name[0]->value);

      $this->drupalGet('/api/v1/global-menu/liikenne', ['language' => $this->getLanguage($langcode)]);
      $this->assertCacheTags([
        'config:rest.resource.helfi_global_menu',
        'global_menu:liikenne',
      ]);
      $this->assertCacheContext('url.query_args');

      $response = $this->getSession()->getPage()->getContent();
      $data = json_decode($response);
      $this->assertSession()->statusCodeEquals(HttpResponse::HTTP_OK);
      $this->assertEquals('liikenne', $data->project[0]->value);
      $this->assertEquals('Liikenne ' . $langcode, $data->name[0]->value);
    }

    // Test depth filter.
    foreach (['en', 'fi'] as $langcode) {
      for ($i = 1; $i <= 3; $i++) {
        $this->drupalGet('/api/v1/global-menu', [
          'language' => $this->getLanguage($langcode),
          'query' => ['max-depth' => $i],
        ]);
        $response = $this->getSession()->getPage()->getContent();
        $data = json_decode($response);

        // Except all levels shown when max depth is set to 0, otherwise
        // show up until defined depth.
        $this->assertMaxDepth($i, $data->liikenne->menu_tree[0]);
      }
    }
  }

  /**
   * Parses max depth for given menu tree.
   *
   * @param object $data
   *   The data to parse.
   * @param int $currentDepth
   *   The current depth.
   *
   * @return int
   *   The current depth.
   */
  private function calculateMaxDepth(object $data, int $currentDepth = 0) : int {
    $currentDepth = $currentDepth + 1;

    foreach ($data->sub_tree as $tree) {
      $currentDepth = $this->calculateMaxDepth($tree, $currentDepth);
    }
    return $currentDepth;
  }

  /**
   * Asserts menu tree's maximum depth.
   *
   * @param int $expectedDepth
   *   The expected maximum depth.
   * @param object $data
   *   The menu tree to parse.
   */
  private function assertMaxDepth(int $expectedDepth, object $data) : void {
    $depth = $this->calculateMaxDepth($data);

    $this->assertEquals($expectedDepth, $depth);
  }

  /**
   * Tests GET request with unpublished entities.
   */
  public function testUnpublishedEntity() : void {
    $user = $this->createUser(permissions:  [
      'restful get helfi_global_menu_collection',
      'view global_menu',
    ]);
    $this->drupalLogin($user);

    $entity = $this->createGlobalMenu('liikenne', 'Liikenne en', []);
    $entity
      ->setPublished()
      ->addTranslation('fi')
      ->setLabel('Liikenne fi')
      ->setUnpublished()
      ->save();

    // English version is published and should be visible.
    $this->drupalGet('/api/v1/global-menu/liikenne', ['language' => $this->getLanguage('en')]);
    $this->assertSession()->statusCodeEquals(HttpResponse::HTTP_OK);

    // Finnish translation is unpublished and should return 403 response.
    $this->drupalGet('/api/v1/global-menu/liikenne', ['language' => $this->getLanguage('fi')]);
    $this->assertSession()->statusCodeEquals(HttpResponse::HTTP_FORBIDDEN);

    // Publish finnish translation and make sure it's visible.
    $entity->getTranslation('fi')->setPublished()->save();
    $this->drupalGet('/api/v1/global-menu/liikenne', ['language' => $this->getLanguage('fi')]);
    $this->assertSession()->statusCodeEquals(HttpResponse::HTTP_OK);
  }

  /**
   * Tests GET request with non-existent global menu.
   */
  public function testGet404Response() : void {
    $user = $this->createUser(permissions:  [
      'restful get helfi_global_menu_collection',
      'view global_menu',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/global-menu/liikenne');
    $this->assertSession()->statusCodeEquals(HttpResponse::HTTP_NOT_FOUND);
  }

  /**
   * Tests POST request permissions.
   */
  public function testPostPermission() : void {
    $this->account = $this->setUpCurrentUser();
    $options = $this->getAuthenticationRequestOptions('POST');
    $options['json'] = $this->getMockJson()['fi'];

    $response = $this->request(
      'POST',
      Url::fromUserInput('/api/v1/global-menu/liikenne'),
      $options
    );
    $data = json_decode((string) $response->getBody());
    $this->assertEquals(HttpResponse::HTTP_FORBIDDEN, $response->getStatusCode());
    $this->assertEquals('You are not authorized to create this global_menu entity', $data->message);

    // Test update permission.
    $this->createGlobalMenu('liikenne', 'Liikenne', []);
    $response = $this->request(
      'POST',
      Url::fromUserInput('/api/v1/global-menu/liikenne'),
      $options
    );
    $data = json_decode((string) $response->getBody());
    $this->assertEquals(HttpResponse::HTTP_FORBIDDEN, $response->getStatusCode());
    $this->assertEquals('You are not authorized to update this global_menu entity', $data->message);
  }

  /**
   * Tests POST validation.
   */
  public function testPostValidation() : void {
    $this->account = $this->createUser(permissions:  [
      'create global_menu',
      'update global_menu',
    ]);
    $options = $this->getAuthenticationRequestOptions('POST');
    // Test required fields.
    $options['json'] = [];
    $response = $this->request(
      'POST',
      Url::fromUserInput('/api/v1/global-menu/liikenne'),
      $options
    );
    $this->assertEquals(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    $data = \json_decode((string) $response->getBody());
    $this->assertEquals('Missing required: menu_tree, site_name', $data->message);

    // Test entity validation failure.
    $options['json'] = [
      'site_name' => 'liikenne',
      'menu_tree' => [],
    ];
    $response = $this->request(
      'POST',
      Url::fromUserInput('/api/v1/global-menu/liikenne'),
      $options
    );
    $data = \json_decode((string) $response->getBody());
    $this->assertEquals(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    $this->assertStringStartsWith("Unprocessable Entity: validation failed.\nmenu_tree:", $data->message);
  }

  /**
   * Tests POST routes.
   */
  public function testPostRoute() : void {
    $this->account = $this->createUser(permissions:  [
      'create global_menu',
      'update global_menu',
      'view global_menu',
    ]);
    $request = function (string $langcode, array $content) : ResponseInterface {
      $options = $this->getAuthenticationRequestOptions('POST');
      $options['json'] = $content;
      return $this->request(
        'POST',
        Url::fromUserInput('/api/v1/global-menu/liikenne', [
          'language' => $this->getLanguage($langcode),
        ]),
        $options
      );
    };
    foreach ($this->getMockJson() as $langcode => $content) {
      $response = $request($langcode, $content);
      // Make sure creating a new entity and translation returns a 201 response
      // code.
      $this->assertEquals(HttpResponse::HTTP_CREATED, $response->getStatusCode());
      $data = \json_decode((string) $response->getBody());
      // Finnish translation is explicitly set to published while english
      // should fall back to unpublished.
      $expectedStatus = $langcode === 'fi';
      $this->assertEquals($expectedStatus, $data->status[0]->value);

      // Re-send the same request to make sure updating an entity returns a 200
      // code.
      $response = $request($langcode, $content);
      $this->assertEquals(HttpResponse::HTTP_OK, $response->getStatusCode());

      // Make sure item was properly translated.
      $data = \json_decode((string) $response->getBody());
      $this->assertEquals('Liikenne ' . $langcode, $data->name[0]->value);
      $this->assertEquals($langcode, $data->langcode[0]->value);

      // Make sure item keeps the published status.
      $this->assertEquals($expectedStatus, $data->status[0]->value);
    }

    // Publish english translation and make sure entity won't get unpublished
    // when we update it without value on 'status' field.
    GlobalMenu::load('liikenne')
      ->getTranslation('en')
      ->setPublished()
      ->save();
    $response = $request('en', $this->getMockJson()['en']);
    $data = \json_decode((string) $response->getBody());
    $this->assertEquals(TRUE, $data->status[0]->value);
  }

}
