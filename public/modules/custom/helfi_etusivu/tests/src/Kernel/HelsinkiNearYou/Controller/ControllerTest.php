<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\Controller;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\LandingPageController;
use Drupal\helfi_etusivu\HelsinkiNearYou\LandingPageImages;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Kernel test for HelsinkiNearYouController.
 *
 * @group helfi_etusivu
 */
class ControllerTest extends KernelTestBase {

  use MediaTypeCreationTrait;
  use ProphecyTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_api_base',
    'diff',
    'helfi_etusivu',
    'system',
    'field',
    'file',
    'image',
    'media',
    'user',
  ];

  /**
   * The placeholder image media.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected MediaInterface $media;

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

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['field', 'file', 'image', 'media', 'system']);

    // Create an image media like the one the controller uses as placeholder.
    $mediaType = $this->createMediaType('image', ['id' => 'image']);
    FieldStorageConfig::create([
      'field_name' => 'field_photographer',
      'entity_type' => 'media',
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_photographer',
      'entity_type' => 'media',
      'bundle' => $mediaType->id(),
    ])->save();

    /** @var object{uri: string} $testImage */
    $testImage = array_first($this->getTestFiles('image'));
    $file = File::create(['uri' => $testImage->uri]);
    $file->save();
    $this->media = Media::create([
      'bundle' => 'image',
      'name' => 'teurastamo_vessi_hamalainen.jpg',
      'field_media_image' => ['target_id' => $file->id(), 'alt' => 'Alt text'],
      'field_photographer' => 'Vessi Hämäläinen',
    ]);
    $this->media->save();

    // Mock the FileUrlGeneratorInterface.
    $this->fileUrlGenerator = $this->createMock(FileUrlGeneratorInterface::class);

    // Mock the ThemeHandlerInterface.
    $this->themeHandler = $this->createMock(ThemeHandlerInterface::class);

    // Set up the container with the mocked services.
    $this->container->set('file_url_generator', $this->fileUrlGenerator);
    $this->container->set('theme_handler', $this->themeHandler);

    $landingPageImages = new LandingPageImages(
      $this->container->get('state'),
      $this->container->get('entity_type.manager'),
      $this->container->get('cache_tags.invalidator'),
    );
    $landingPageImages->setMediaIds([
      'hero' => (int) $this->media->id(),
      'liftup' => (int) $this->media->id(),
    ]);

    $this->controller = new LandingPageController(
      $this->fileUrlGenerator,
      $this->themeHandler,
      $this->container->get('entity.repository'),
      $landingPageImages,
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
    // The texts are hard-coded in the template.
    $this->assertArrayNotHasKey('#title', $build);
    $this->assertArrayNotHasKey('#description', $build);
    $this->assertEquals($image_path . 'walking_by_houses-513x513.png', $build['#illustration_url_1x']);
    $this->assertEquals($image_path . 'walking_by_houses-1026x1026.png', $build['#illustration_url_2x']);
    $this->assertEquals('Lille Santanen', (string) $build['#illustration_caption']);

    $this->assertMediaImage($build['#hero_image'], 'hero', 'hero__diagonal');
    $this->assertEquals('Vessi Hämäläinen', $build['#hero_image_author']);

    $this->assertEquals('helfi_etusivu_near_you', $build['#search_form']['#form_id']);
    $this->assertContains(LandingPageImages::CACHE_TAG, $build['#cache']['tags']);

    $this->assertMediaImage($build['#liftup_image'], 'image', 'liftup_left_right');
    $this->assertEquals([
      'caption_identifier_class' => 'image__caption',
      'credit' => 'Vessi Hämäläinen',
    ], $build['#liftup_image_caption']);

    // Hero and liftup must not share the media object, as the altered image
    // style is set on it and rendering happens later.
    $this->assertNotSame($build['#hero_image']['#media'], $build['#liftup_image']['#media']);
  }

  /**
   * Tests that deleted media leaves out the images.
   */
  public function testContentWithoutMedia(): void {
    $this->media->delete();

    $themeExtensionMock = $this->prophesize(Extension::class);
    $themeExtensionMock->getPath()->willReturn('themes/custom/hdbt_subtheme');
    $this->themeHandler->method('getTheme')->willReturn($themeExtensionMock->reveal());
    $generatedUrlMock = $this->prophesize(GeneratedUrl::class);
    $generatedUrlMock->getGeneratedUrl()->willReturn('/');
    $fileUrlMock = $this->prophesize(Url::class);
    $fileUrlMock->toString(TRUE)->willReturn($generatedUrlMock->reveal());
    $this->fileUrlGenerator->method('generate')->willReturn($fileUrlMock->reveal());

    $build = $this->controller->content();

    $this->assertArrayNotHasKey('#hero_image', $build);
    $this->assertArrayNotHasKey('#hero_image_author', $build);
    $this->assertArrayNotHasKey('#liftup_image', $build);
    $this->assertArrayNotHasKey('#liftup_image_caption', $build);
    $this->assertArrayHasKey('#search_form', $build);
  }

  /**
   * Asserts a media render array is rendered like a paragraph's image field.
   *
   * @param array $build
   *   The media render array.
   * @param string $viewMode
   *   The expected view mode.
   * @param string $imageStyle
   *   The expected responsive image style.
   *
   * @phpstan-param array<string, mixed> $build
   */
  private function assertMediaImage(array $build, string $viewMode, string $imageStyle): void {
    $this->assertEquals($this->media->id(), $build['#media']->id());
    $this->assertEquals($viewMode, $build['#view_mode']);
    $this->assertArrayNotHasKey('keys', $build['#cache']);
    $this->assertEquals(
      $imageStyle,
      $build['#media']->get('field_media_image')->first()->altered_image_style
    );
  }

}
