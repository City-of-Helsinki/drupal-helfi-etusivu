<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Token;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\helfi_etusivu\Token\NewsNodeImageBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests news node image URI building against real field values.
 */
#[Group('helfi_etusivu')]
#[CoversClass(NewsNodeImageBuilder::class)]
#[RunTestsInSeparateProcesses]
class NewsNodeImageBuilderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'media',
    'node',
  ];

  /**
   * The builder under test.
   */
  private NewsNodeImageBuilder $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('media');
    $this->installConfig(['system', 'user', 'node', 'file', 'image', 'media']);

    NodeType::create([
      'type' => 'news_item',
      'name' => 'News item',
    ])->save();

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    MediaType::create([
      'id' => 'image',
      'label' => 'Image',
      'source' => 'image',
      'source_configuration' => [
        'source_field' => 'field_media_image',
      ],
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_media_image',
      'entity_type' => 'media',
      'type' => 'image',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_media_image',
      'entity_type' => 'media',
      'bundle' => 'image',
      'label' => 'Image',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_main_image',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_main_image',
      'entity_type' => 'node',
      'bundle' => 'news_item',
      'label' => 'Main image',
      'settings' => [
        'handler' => 'default:media',
        'handler_settings' => [
          'target_bundles' => ['image' => 'image'],
        ],
      ],
    ])->save();

    $this->builder = new NewsNodeImageBuilder();
  }

  /**
   * Tests that a node without the main image field produces no URI.
   */
  public function testBuildUriReturnsNullWithoutField() : void {
    $node = Node::create([
      'type' => 'page',
      'title' => 'No image field',
    ]);
    $node->save();

    $this->assertNull($this->builder->buildUri($node));
  }

  /**
   * Tests that an empty main image reference produces no URI.
   */
  public function testBuildUriReturnsNullWhenReferenceIsEmpty() : void {
    $node = Node::create([
      'type' => 'news_item',
      'title' => 'Empty reference',
    ]);
    $node->save();

    $this->assertNull($this->builder->buildUri($node));
  }

  /**
   * Tests that a media item with an empty image field produces no URI.
   */
  public function testBuildUriReturnsNullWhenImageFieldIsEmpty() : void {
    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Empty image',
    ]);
    $media->save();

    $node = Node::create([
      'type' => 'news_item',
      'title' => 'Empty image',
      'field_main_image' => ['target_id' => $media->id()],
    ]);
    $node->save();

    $this->assertNull($this->builder->buildUri($node));
  }

  /**
   * Tests that the referenced file URI is returned.
   */
  public function testBuildUriReturnsFileUri() : void {
    $file = File::create([
      'uri' => 'public://news.jpg',
    ]);
    $file->setPermanent();
    $file->save();

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'News image',
      'field_media_image' => ['target_id' => $file->id()],
    ]);
    $media->save();

    $node = Node::create([
      'type' => 'news_item',
      'title' => 'With image',
      'field_main_image' => ['target_id' => $media->id()],
    ]);
    $node->save();

    $this->assertSame('public://news.jpg', $this->builder->buildUri($node));
  }

}
