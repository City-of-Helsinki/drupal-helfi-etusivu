<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\ChainBreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\Tests\helfi_api_base\Traits\EnvironmentResolverTrait;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests the Helsinki Near You breadcrumb builder.
 */
#[Group("helfi_etusivu")]
#[RunTestsInSeparateProcesses]
class HelsinkiNearYouBreadcrumbBuilderTest extends KernelTestBase {

  use EnvironmentResolverTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_api_base',
    'helfi_platform_config',
    'helfi_etusivu',
    'config_rewrite',
    'system',
    'path_alias',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setActiveProject(Project::ETUSIVU, EnvironmentEnum::Local);
  }

  /**
   * Tests breadcrumb on sub-pages (events, roadworks, feedback) with address.
   */
  #[DataProvider('pagesProvider')]
  public function testSubPageWithAddress(string $routeName, string $path, int $expectedLinkCount): void {
    $breadcrumb = $this->buildBreadcrumb($routeName, $path, 'Kalevankatu 2');

    $links = $breadcrumb->getLinks();
    $this->assertCount($expectedLinkCount, $links);

    // This link is injected by helfi_platform_config_system_breadcrumb_alter.
    $this->assertEquals('Front page', $links[0]->getText());
    $this->assertEquals('<front>', $links[0]->getUrl()->getRouteName());

    $this->assertEquals('Helsinki near you', $links[1]->getText());

    if ($expectedLinkCount > 2) {
      $this->assertEquals('helfi_etusivu.helsinki_near_you', $links[1]->getUrl()->getRouteName());

      // Address link should point to results page with query parameter.
      $addressLinkText = $links[2]->getText();
      $this->assertInstanceOf(TranslatableMarkup::class, $addressLinkText);
      $this->assertStringContainsString('Kalevankatu 2', (string) $addressLinkText);
    }

    if ($expectedLinkCount > 3) {
      $this->assertEquals('helfi_etusivu.helsinki_near_you_results', $links[2]->getUrl()->getRouteName());
      $this->assertEquals('Kalevankatu 2', $links[2]->getUrl()->getOption('query')['home_address']);

      $this->assertNotEmpty($links[3]->getText());
    }

    // Last link should always be the current page.
    $this->assertEquals('<none>', array_last($links)->getUrl()->getRouteName());

    // All previous links should have a real route.
    foreach (array_slice($links, 0, -1) as $link) {
      $this->assertNotEquals('<none>', $link->getUrl()->getRouteName());
    }
  }

  /**
   * Data provider for all pages.
   *
   * @return array<mixed>
   *   Test data.
   */
  public static function pagesProvider(): array {
    return [
      'landing page' => [
        'helfi_etusivu.helsinki_near_you',
        '/helsinki-near-you',
        2,
      ],
      'results' => [
        'helfi_etusivu.helsinki_near_you_results',
        '/helsinki-near-you/results',
        3,
      ],
      'events' => [
        'helfi_etusivu.helsinki_near_you_events',
        '/helsinki-near-you/events',
        4,
      ],
      'roadworks' => [
        'helfi_etusivu.helsinki_near_you_roadworks',
        '/helsinki-near-you/roadworks',
        4,
      ],
      'feedback' => [
        'helfi_etusivu.helsinki_near_you_feedback',
        '/helsinki-near-you/feedback',
        4,
      ],
    ];
  }

  /**
   * Builds a breadcrumb for the given route.
   */
  private function buildBreadcrumb(string $routeName, string $path, ?string $address = NULL): Breadcrumb {
    $query = $address ? ['home_address' => $address] : [];
    $request = Request::create($path, 'GET', $query);

    $route = new Route($path);
    $routeMatch = new RouteMatch($routeName, $route);

    $requestStack = $this->container->get('request_stack');
    $requestStack->push($request);

    try {
      return $this->container->get(ChainBreadcrumbBuilderInterface::class)
        ->build($routeMatch);
    }
    finally {
      $requestStack->pop();
    }
  }

}
