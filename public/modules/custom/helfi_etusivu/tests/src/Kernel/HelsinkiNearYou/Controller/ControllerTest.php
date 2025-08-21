<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\Controller;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\LandingPageController;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Kernel test for HelsinkiNearYouController.
 *
 * @group helfi_etusivu
 */
class ControllerTest extends KernelTestBase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_etusivu',
    'system',
  ];

  /**
   * The controller under test.
   *
   * @var \Drupal\helfi_etusivu\HelsinkiNearYou\Controller\LandingPageController
   */
  protected LandingPageController $controller;

  /**
   * The mocked file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected FileUrlGeneratorInterface|MockObject $fileUrlGenerator;

  /**
   * The mocked theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ThemeHandlerInterface|MockObject $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the FileUrlGeneratorInterface.
    $this->fileUrlGenerator = $this->createMock(FileUrlGeneratorInterface::class);

    // Mock the ThemeHandlerInterface.
    $this->themeHandler = $this->createMock(ThemeHandlerInterface::class);

    // Set up the container with the mocked services.
    $this->container->set('file_url_generator', $this->fileUrlGenerator);
    $this->container->set('theme_handler', $this->themeHandler);

    $this->controller = new LandingPageController(
      $this->fileUrlGenerator,
      $this->themeHandler,
    );
  }

  /**
   * Tests the content() method.
   */
  public function testContent(): void {
    $hdbt_subtheme_path = 'themes/custom/hdbt_subtheme';
    $image_path = $hdbt_subtheme_path . '/src/images/';

    $themeExtensionMock = $this->prophesize(Extension::class);
    $themeExtensionMock->getPath()->willReturn($hdbt_subtheme_path);
    $this->themeHandler->expects($this->once())
      ->method('getTheme')
      ->with('hdbt_subtheme')
      ->willReturn($themeExtensionMock->reveal());

    $fileUrlMock = $this->prophesize(Url::class);
    $generatedUrlMock = $this->prophesize(GeneratedUrl::class);

    $this->fileUrlGenerator->expects($this->once())
      ->method('generate')
      ->with($image_path)
      ->willReturn($fileUrlMock->reveal());

    $fileUrlMock->toString(TRUE)->willReturn($generatedUrlMock->reveal());
    $generatedUrlMock->getGeneratedUrl()->willReturn($image_path);

    $build = $this->controller->content();

    $this->assertIsArray($build);
    $this->assertArrayHasKey('#theme', $build);
    $this->assertEquals('helsinki_near_you_landing_page', $build['#theme']);
    $this->assertEquals('Start by entering your street address', (string) $build['#title']);
    $this->assertEquals(
      'Enter your street address in the search field above to find services, events and news related to your neighbourhood.',
      (string) $build['#description']
    );
    $this->assertEquals($image_path . 'walking_by_houses-513x513.png', $build['#illustration_url_1x']);
    $this->assertEquals($image_path . 'walking_by_houses-1026x1026.png', $build['#illustration_url_2x']);
    $this->assertEquals('Picture: Lille Santanen', (string) $build['#illustration_caption']);
  }

}
