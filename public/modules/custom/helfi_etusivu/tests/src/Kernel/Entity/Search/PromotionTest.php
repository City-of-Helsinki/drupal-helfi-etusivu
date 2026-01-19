<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity\Search;

use Drupal\Core\Session\AccountInterface;
use Drupal\helfi_etusivu\Entity\Search\Promotion;
use Drupal\Tests\helfi_etusivu\Kernel\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests promotion entity access.
 */
#[Group('helfi_etusivu')]
class PromotionTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
    'text',
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

    $this->installEntitySchema('helfi_search_promotion');

    // Create a dummy user before tests to make sure our actual user is not
    // UID1 and getting all permissions automatically.
    $this->drupalCreateUser();

    $this->promotion = Promotion::create([
      'title' => 'Test Promotion',
      'description' => 'Test description',
      'link' => 'https://example.com',
    ]);
    $this->promotion->save();
  }

  /**
   * Asserts entity access for given operations.
   *
   * @param array $ops
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

    $this->assertEntityAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $this->promotion, $account);
  }

}
