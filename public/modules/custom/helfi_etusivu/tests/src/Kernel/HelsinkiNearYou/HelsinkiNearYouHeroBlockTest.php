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
    'big_pipe',
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
    $plugin_definition = [
      'provider' => 'helfi_etusivu',
    ];

    $routeMatch = $this->prophesize(RouteMatchInterface::class);
    $routeMatch->getRouteName()->willReturn('helfi_etusivu.helsinki_near_you');
    $this->container->set(RouteMatchInterface::class, $routeMatch->reveal());
    $block = HelsinkiNearYouHeroBlock::create($this->container, [], 'helsinki_near_you_hero_block', $plugin_definition);

    $build = $block->build();

    $this->assertIsArray($build);
    $this->assertArrayHasKey('helsinki_near_you_hero_block', $build);

    $content = $build['helsinki_near_you_hero_block'];
    $this->assertArrayHasKey('#form', $content);
    $this->assertArrayHasKey('#theme', $content);
    $this->assertEquals('helsinki_near_you_hero_block', $content['#theme']);
    $this->assertEquals('Helsinki near you', (string) $content['#hero_title']);
    $this->assertEquals(
      'Discover city services, events and news near you. Start by entering your street address.',
      (string) $content['#hero_description']
    );
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
