<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity\Search;

use Drupal\Core\Session\AccountInterface;
use Drupal\helfi_etusivu\Entity\Search\Promotion;
use Drupal\helfi_etusivu\Entity\Search\PromotionType;
use Drupal\Tests\helfi_etusivu\Kernel\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests promotion entity access.
 */
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
class PromotionTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
    'helfi_api_base',
    'text',
    'scheduler',
    'helfi_etusivu',
  ];

  /**
   * The promotion entity.
   */
  protected Promotion $promotion;

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

    // Create a dummy user before tests to make sure our actual user is not
    // UID1 and getting all permissions automatically.
    $this->drupalCreateUser();

    $this->promotion = Promotion::create([
      'bundle' => 'promotion',
      'title' => 'Test Promotion',
      'description' => 'Test description',
      'link' => 'https://example.com',
    ]);
    $this->promotion->save();
  }

  /**
   * Asserts entity access for given operations.
   *
   * @param array<mixed> $ops
   *   The ops [operation => expected access (bool)].
   * @param \Drupal\helfi_etusivu\Entity\Search\Promotion $entity
   *   The entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account.
   */
  private function assertEntityAccess(array $ops, Promotion $entity, ?AccountInterface $account = NULL): void {
    foreach ($ops as $op => $allowed) {
      $access = $entity->access($op, $account, TRUE);
      $this->assertEquals($allowed, $access->isAllowed(), "Operation '$op' should be " . ($allowed ? 'allowed' : 'denied'));
    }
  }

  /**
   * Tests that anonymous users cannot access the entity.
   */
  public function testAnonymousAccess(): void {
    $this->assertEntityAccess([
      'view' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $this->promotion);
  }

  /**
   * Tests that users with admin permission can access all operations.
   */
  public function testAdminAccess(): void {
    $account = $this->drupalCreateUser([
      'administer search promotions',
    ]);

    $this->assertInstanceOf(AccountInterface::class, $account);
    $this->assertEntityAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $this->promotion, $account);
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
