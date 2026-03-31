<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Search\Controller;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for SearchPageController.
 */
class SearchPageControllerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_etusivu',
    'helfi_api_base',
    'helfi_search',
    'system',
  ];

  /**
   * Tests that the search page content is rendered correctly.
   */
  public function testSearchPageContent(): void {
    $controller = SearchPageController::create($this->container);
    $result = $controller->content();
    $this->assertEquals('helfi_etusivu_site_search', $result['#theme']);
    $this->assertArrayHasKey('drupalSettings', $result['#attached']);
  }

}
