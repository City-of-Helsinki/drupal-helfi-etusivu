<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\LlmsTxt;

use Drupal\helfi_etusivu\LlmsTxt\Controller;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the /llms.txt controller.
 */
#[Group('helfi_etusivu')]
#[CoversClass(Controller::class)]
class ControllerTest extends KernelTestBase {

  use ApiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'helfi_api_base',
    'helfi_etusivu',
  ];

  /**
   * Tests that the route serves the configured content as markdown.
   */
  public function testRouteServesContent(): void {
    $content = "# llms.txt\n\nHello, robots.";

    $this->config('helfi_etusivu.llms_txt')
      ->set('content', $content)
      ->save();

    $request = $this->getMockedRequest('/llms.txt');
    $response = $this->processRequest($request);

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertSame($content, $response->getContent());
  }

}
