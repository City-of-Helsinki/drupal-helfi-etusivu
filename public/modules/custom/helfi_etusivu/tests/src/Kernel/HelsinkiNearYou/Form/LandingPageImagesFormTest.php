<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\helfi_etusivu\HelsinkiNearYou\Enum\LandingPageImage;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\LandingPageImagesForm;
use Drupal\helfi_etusivu\HelsinkiNearYou\LandingPageImages;
use Drupal\helfi_etusivu\HelsinkiNearYou\LandingPageImagesMediaLibraryOpener;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the Helsinki near you images form.
 *
 * @group helfi_etusivu
 */
class LandingPageImagesFormTest extends KernelTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'allowed_formats',
    'breakpoint',
    'content_translation',
    'crop',
    'diff',
    'entity',
    'entity_reference_revisions',
    'field',
    'file',
    'filter',
    'focal_point',
    'hdbt_admin_tools',
    'helfi_api_base',
    'helfi_etusivu',
    'helfi_media',
    'helfi_paragraphs_hero',
    'helfi_paragraphs_liftup_with_image',
    'image',
    'language',
    'link',
    'linkit',
    'media',
    'media_library',
    'options',
    'paragraphs',
    'paragraphs_library',
    'responsive_image',
    'system',
    'text',
    'user',
    'views',
  ];

  /**
   * An image media.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected MediaInterface $image;

  /**
   * A media that is not an image.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected MediaInterface $document;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installEntitySchema('crop');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig([
      'system',
      'paragraphs',
      'media',
      'image',
      'media_library',
      'crop',
      'focal_point',
      'helfi_media',
      'helfi_paragraphs_hero',
      'helfi_paragraphs_liftup_with_image',
      'hdbt_admin_tools',
      'filter',
    ]);

    $this->setUpCurrentUser(permissions: [
      'administer helsinki near you images',
      'view media',
    ]);

    /** @var object{uri: string} $testImage */
    $testImage = array_first($this->getTestFiles('image'));
    $file = File::create(['uri' => $testImage->uri]);
    $file->save();
    $this->image = Media::create([
      'bundle' => 'image',
      'name' => 'Image',
      'field_media_image' => ['target_id' => $file->id(), 'alt' => 'Alt text'],
    ]);
    $this->image->save();

    $this->createMediaType('file', ['id' => 'test_document']);
    /** @var object{uri: string} $testText */
    $testText = array_first($this->getTestFiles('text'));
    $textFile = File::create(['uri' => $testText->uri]);
    $textFile->save();
    $this->document = Media::create([
      'bundle' => 'test_document',
      'name' => 'Document',
      'field_media_file' => $textFile->id(),
    ]);
    $this->document->save();
  }

  /**
   * Tests the route and its permission.
   */
  public function testAccess(): void {
    $url = Url::fromRoute('helfi_etusivu.helsinki_near_you_images');
    $this->assertEquals('/admin/tools/appearance-settings/helsinki-near-you', $url->toString());
    $this->assertTrue($url->access());
    $this->assertFalse($url->access($this->createUser(['view media'])));
  }

  /**
   * Tests that the fields use the paragraphs' image field widgets.
   */
  public function testBuildForm(): void {
    $form = $this->container->get('form_builder')->getForm(LandingPageImagesForm::class);

    $this->assertEquals('Hero image', (string) $form['field_hero_image']['widget']['#title']);
    $this->assertEquals('Liftup image', (string) $form['field_liftup_with_image_image']['widget']['#title']);

    foreach (['field_hero_image', 'field_liftup_with_image_image'] as $fieldName) {
      $widget = $form[$fieldName]['widget'];
      // Media library widget with the image media type, like the paragraphs.
      $this->assertArrayHasKey('open_button', $widget);
      $state = $widget['open_button']['#media_library_state'];
      $this->assertEquals(['image'], array_values($state->getAllowedTypeIds()));

      // The media library is opened with the form's own opener, so access
      // to it follows the form's permission.
      $this->assertEquals(LandingPageImagesMediaLibraryOpener::ID, $state->getOpenerId());
      $uiBuilder = $this->container->get('media_library.ui_builder');
      $this->assertTrue($uiBuilder->checkAccess($this->container->get('current_user'), $state)->isAllowed());
      $this->assertFalse($uiBuilder->checkAccess($this->createUser(['view media']), $state)->isAllowed());

      // The selection is passed to the widget.
      $commands = $this->container->get(LandingPageImagesMediaLibraryOpener::ID)
        ->getSelectionResponse($state, [1, 2])
        ->getCommands();
      $this->assertEquals("[data-media-library-widget-value=\"$fieldName\"]", $commands[0]['selector']);
      $this->assertEquals(['1,2'], $commands[0]['args']);
      $this->assertEquals("[data-media-library-widget-update=\"$fieldName\"]", $commands[1]['selector']);
    }

    // Required like in the paragraphs.
    $this->assertFalse($form['field_hero_image']['widget']['#required']);
    $this->assertTrue($form['field_liftup_with_image_image']['widget']['#required']);
  }

  /**
   * Tests validation and saving.
   */
  public function testSubmitForm(): void {
    $images = $this->container->get(LandingPageImages::class);

    // The liftup image is required.
    $errors = $this->submitForm();
    $this->assertEquals(['field_liftup_with_image_image'], array_keys($errors));
    $this->assertNull($images->getMediaId(LandingPageImage::Liftup));

    // Only image media are allowed.
    $images->setMediaIds(['hero' => (int) $this->document->id(), 'liftup' => (int) $this->image->id()]);
    $errors = $this->submitForm([
      'field_hero_image' => $this->selection($this->document),
      'field_liftup_with_image_image' => $this->selection($this->image),
    ]);
    $this->assertEquals(['field_hero_image'], array_keys($errors));

    // Valid images are saved.
    $images->setMediaIds(['hero' => (int) $this->image->id(), 'liftup' => (int) $this->image->id()]);
    $errors = $this->submitForm([
      'field_hero_image' => $this->selection($this->image),
      'field_liftup_with_image_image' => $this->selection($this->image),
    ]);
    $this->assertEmpty($errors);
    $this->assertEquals($this->image->id(), $images->getMedia(LandingPageImage::Hero)?->id());
    $this->assertEquals($this->image->id(), $images->getMedia(LandingPageImage::Liftup)?->id());

    // Images can be removed. In the UI this is done with the widget's remove
    // button, which updates the widget state with AJAX.
    $images->setMediaIds(['hero' => NULL]);
    $this->assertNull($images->getMediaId(LandingPageImage::Hero));
    $this->assertEquals($this->image->id(), $images->getMediaId(LandingPageImage::Liftup));

    // Deleted media is not returned.
    $this->image->delete();
    $this->assertNull($images->getMedia(LandingPageImage::Liftup));
  }

  /**
   * Submits the form.
   *
   * @param array $values
   *   The submitted values.
   *
   * @return array
   *   The form errors keyed by element name.
   *
   * @phpstan-param array<string, mixed> $values
   * @phpstan-return array<string, mixed>
   */
  private function submitForm(array $values = []): array {
    $formState = (new FormState())->setValues($values);
    $this->container->get('form_builder')->submitForm(LandingPageImagesForm::class, $formState);
    return $formState->getErrors();
  }

  /**
   * Gets the media library widget value selecting the given media.
   *
   * The widget only accepts selections that exist in its state, which in the
   * UI are added with the media library. Here they come from the stored
   * images the form is built with.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media.
   *
   * @return array
   *   The widget value.
   *
   * @phpstan-return array<string, mixed>
   */
  private function selection(MediaInterface $media): array {
    return ['selection' => [['target_id' => $media->id(), 'weight' => 0]]];
  }

}
