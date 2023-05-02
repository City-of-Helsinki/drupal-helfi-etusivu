<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_etusivu\Kernel;

use Drupal\Core\Url;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\Tests\helfi_api_base\Kernel\ApiKernelTestBase;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests dashboard controller.
 *
 * @group helfi_etusivu
 */
class DashboardControllerTest extends ApiKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_api_base',
    'helfi_etusivu',
  ];

  /**
   * Asserts that given routes return the given HTTP response code.
   *
   * @param array $routes
   *   The routes to test.
   * @param int $expectedCode
   *   The expected HTTP response code.
   */
  private function assertRoutePermission(array $routes, int $expectedCode) : void {
    foreach ($routes as $route) {
      $request = Request::create($route->toString());
      $response = $this->processRequest($request);
      $this->assertEquals($expectedCode, $response->getStatusCode());
    }
  }

  /**
   * Tests endpoint permissions.
   */
  public function testPermissions() : void {
    $routes = [
      Url::fromRoute('helfi_etusivu.dashboard.index'),
      Url::fromRoute('helfi_etusivu.dashboard.status'),
      Url::fromRoute('helfi_etusivu.dashboard.api', options: [
        'query' => ['project' => Project::ETUSIVU, 'environment' => 'local'],
      ]),
    ];

    $this->assertRoutePermission($routes, 403);

    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalSetCurrentUser($account);

    $this->config('helfi_api_base.api_accounts')
      ->set('vault', [
        [
          'id' => Project::ETUSIVU . '_local',
          'plugin' => 'authorization_token',
          'data' => base64_encode($account->getAccountName() . ':' . $account->pass_raw),
        ],
      ])
      ->save();

    $this->container->set('http_client', $this->createMockHttpClient([
      new Response(body: '[]'),
    ]));
    $this->assertRoutePermission($routes, 200);
  }

}
