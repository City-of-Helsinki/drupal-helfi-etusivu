<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\helfi_etusivu\Plugin\Block\HelsinkiNearYouHeroBlock;
use Symfony\Component\DependencyInjection\Loader\Configurator\Traits\PropertyTrait;

/**
 * Kernel tests for HelsinkiNearYouHeroBlock.
 *
 * @group helfi_etusivu
 */
class HelsinkiNearYouHeroBlockTest extends KernelTestBase {

  use PropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'block',
    'helfi_api_base',
    'diff',
    'helfi_etusivu',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Proper schemas and configs required for kernel tests.
    $this->installEntitySchema('user');
    $this->installConfig(['system']);
  }

  /**
   * Tests the build method.
   */
  public function testBuildMethod(): void {
    $build = $this->buildBlock('helfi_etusivu.helsinki_near_you_roadworks');

    $this->assertIsArray($build);
    $this->assertArrayHasKey('helsinki_near_you_hero_block', $build);

    $content = $build['helsinki_near_you_hero_block'];
    $this->assertArrayHasKey('#theme', $content);
    $this->assertEquals('helsinki_near_you_hero_block', $content['#theme']);
    $this->assertNull($content['#form']);
  }

  /**
   * Tests that the block is empty on the landing page and unknown routes.
   */
  public function testBuildIsEmpty(): void {
    $this->assertEquals([], $this->buildBlock('helfi_etusivu.helsinki_near_you'));
    $this->assertEquals([], $this->buildBlock('system.404'));
  }

  /**
   * Builds the block for the given route.
   *
   * @param string $routeName
   *   The route name.
   *
   * @return array
   *   The block render array.
   *
   * @phpstan-return array<string, mixed>
   */
  private function buildBlock(string $routeName): array {
    $routeMatch = $this->prophesize(RouteMatchInterface::class);
    $routeMatch->getRouteName()->willReturn($routeName);
    $this->container->set(RouteMatchInterface::class, $routeMatch->reveal());
    $block = HelsinkiNearYouHeroBlock::create($this->container, [], 'helsinki_near_you_hero_block', [
      'provider' => 'helfi_etusivu',
    ]);

    return $block->build();
  }

  /**
   * Tests cache contexts.
   */
  public function testCacheContexts(): void {
    $plugin_definition = [
      'provider' => 'helfi_etusivu',
    ];

    /** @var \Drupal\helfi_etusivu\Plugin\Block\HelsinkiNearYouHeroBlock $block */
    $block = HelsinkiNearYouHeroBlock::create($this->container, [], 'helsinki_near_you_hero_block', $plugin_definition);
    $contexts = $block->getCacheContexts();

    $this->assertContains('route', $contexts);
  }

}
