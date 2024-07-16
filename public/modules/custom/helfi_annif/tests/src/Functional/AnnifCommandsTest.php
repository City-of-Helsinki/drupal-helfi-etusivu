<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_annif\Functional;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\helfi_annif\TextConverter\RenderTextConverter;
use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests drush command.
 *
 * @group helfi_annif
 */
class AnnifCommandsTest extends BrowserTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
    'node',
    'helfi_annif',
    'helfi_etusivu',
    'datetime',
    'paragraphs',
    'helfi_node_news_item',
    'readonly_field_widget',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Test node type',
    ]);

    // Enable RenderTextConverter.
    \Drupal::service(EntityDisplayRepositoryInterface::class)
      ->getViewDisplay('node', 'page', RenderTextConverter::TEXT_CONVERTER_VIEW_MODE)
      ->setComponent('body', [
        'label' => 'hidden',
        'type' => 'text_default',
      ])
      ->setStatus(1)
      ->save();
  }

  /**
   * Tests helfi:preview-text command.
   */
  public function testTextPreviewCommand() {
    $node = $this->drupalCreateNode([
      'title' => 'Hello, world!',
      'type' => 'page',
      'body' => [
        'value' => 'Foobar',
      ],
    ]);

    $node
      ->addTranslation('fi', [
        'title' => 'Hei, maailma!',
        'body' => [
          'value' => 'Barfoo',
        ],
      ])
      ->save();

    $this->drush('helfi:preview-text', [
      'entity_type' => $node->getEntityTypeId(),
      'id' => $node->id(),
    ]);

    $output = $this->getOutputRaw();
    $this->assertStringContainsString("Hello, world!", $output);
    $this->assertStringContainsString("Foobar", $output);

    $this->drush('helfi:preview-text', [
      'entity_type' => $node->getEntityTypeId(),
      'id' => $node->id(),
    ], [
      'language' => 'fi',
    ]);

    $output = $this->getOutputRaw();
    $this->assertStringContainsString("Hei, maailma!", $output);
    $this->assertStringContainsString("Barfoo", $output);
  }

}
