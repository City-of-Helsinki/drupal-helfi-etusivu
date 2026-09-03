<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity\Search;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\helfi_etusivu\Entity\Search\Promotion;
use Drupal\helfi_etusivu\Entity\Search\PromotionType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests promotion entity access.
 */
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
class PromotionTest extends SearchEntityTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
    'text',
    'scheduler',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('helfi_search_promotion_type');
    $this->installEntitySchema('helfi_search_promotion');

    $type = PromotionType::create(['id' => 'promotion', 'label' => 'Promotion']);
    $type->setThirdPartySetting('scheduler', 'publish_enable', TRUE);
    $type->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE);
    $type->save();

    $this->container->get(ContentTranslationManagerInterface::class)
      ->setEnabled('helfi_search_promotion', 'promotion', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function createTestEntity(): ContentEntityInterface {
    $promotion = Promotion::create([
      'bundle' => 'promotion',
      'title' => 'Test Promotion',
      'description' => 'Test description',
      'link' => 'https://example.com',
    ]);
    $promotion->addTranslation('sv', [
      'title' => 'Test Promotion sv',
      'description' => 'Test description sv',
      'link' => 'https://example.com',
    ]);
    $promotion->save();

    return $promotion;
  }

  /**
   * Tests that promotions can be rendered.
   */
  public function testRender(): void {
    $this->setCurrentUser($this->drupalCreateUser([self::ADMIN_PERMISSION]));

    $this->rebuildRouter();

    $build = $this->container->get(EntityTypeManagerInterface::class)
      ->getViewBuilder('helfi_search_promotion')
      ->view($this->createTestEntity());

    $markup = (string) $this->container->get(RendererInterface::class)
      ->renderInIsolation($build);

    $this->assertStringContainsString('Test description', $markup);
    $this->assertStringContainsString('Back to search promotions', $markup);
  }

  /**
   * Tests that titles and keywords are trimmed on save.
   */
  public function testWhitespaceIsTrimmedOnSave(): void {
    $promotion = Promotion::create([
      'bundle' => 'promotion',
      'title' => '  Advisory services ',
      'description' => 'Test description',
      'link' => 'https://example.com',
      // Includes Unicode whitespace: NARROW NO-BREAK SPACE (U+202F)
      // and NO-BREAK SPACE (U+00A0).
      'keywords' => [' Advisory services', "Advisory\u{202F}", "\u{00A0}", '  '],
    ]);
    $promotion->save();

    $reloaded = Promotion::load($promotion->id());
    $this->assertSame('Advisory services', $reloaded->label());
    $this->assertSame(['Advisory services', 'Advisory'], $reloaded->getKeywords());
  }

  /**
   * Tests scheduled publishing and unpublishing of a promotion.
   */
  public function testSchedulerPublishAndUnpublish(): void {
    $past = \Drupal::time()->getRequestTime() - 60;

    // Create an unpublished promotion with publish_on in the past.
    $promotion = Promotion::create([
      'bundle' => 'promotion',
      'title' => 'Scheduled promotion',
      'description' => 'Test description',
      'link' => 'https://example.com',
      'publish_on' => $past,
    ]);
    $promotion->setUnpublished()->save();
    $this->assertFalse($promotion->isPublished());

    /** @var \Drupal\scheduler\SchedulerManager $scheduler */
    $scheduler = $this->container->get('scheduler.manager');
    $scheduler->publish();

    $reloaded = Promotion::load($promotion->id());
    $this->assertTrue($reloaded->isPublished());
    $this->assertNull($reloaded->get('publish_on')->value);

    // Now set unpublish_on in the past and run the unpublish phase.
    $reloaded->set('unpublish_on', $past)->save();
    $scheduler->unpublish();

    $reloaded = Promotion::load($promotion->id());
    $this->assertFalse($reloaded->isPublished());
    $this->assertNull($reloaded->get('unpublish_on')->value);
  }

}
