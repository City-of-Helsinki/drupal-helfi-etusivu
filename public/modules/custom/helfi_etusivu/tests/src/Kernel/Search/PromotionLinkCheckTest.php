<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Search;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\helfi_etusivu\Entity\Search\Promotion;
use Drupal\helfi_etusivu\Entity\Search\PromotionType;
use Drupal\helfi_etusivu\Hook\PromotionHooks;
use Drupal\helfi_etusivu\Search\PromotionLinkChecker;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests link health monitoring for search promotions.
 */
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
class PromotionLinkCheckTest extends KernelTestBase {

  use ApiTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
    'user',
    'system',
    'helfi_api_base',
    'helfi_etusivu',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('helfi_search_promotion_type');
    $this->installEntitySchema('helfi_search_promotion');

    // Create uid1 so subsequent test users do not inherit super-admin perms.
    $this->createUser();

    $type = PromotionType::create(['id' => 'promotion', 'label' => 'Promotion']);
    $type->save();
  }

  /**
   * Creates and saves a published promotion.
   */
  private function createPromotion(string $uri = 'https://example.com'): Promotion {
    $promotion = Promotion::create([
      'bundle' => 'promotion',
      'title' => 'Test Promotion',
      'description' => 'Test description',
      'link' => $uri,
    ]);
    $promotion->save();
    return $promotion;
  }

  /**
   * Returns the link checker, configured with the given mock responses.
   *
   * @param array<\Psr\Http\Message\ResponseInterface|\GuzzleHttp\Exception\GuzzleException> $responses
   *   Responses or exceptions to be returned in order by the mock client.
   */
  private function checkerWithResponses(array $responses): PromotionLinkChecker {
    $this->container->set('http_client', $this->createMockHttpClient($responses));
    return $this->container->get(PromotionLinkChecker::class);
  }

  /**
   * Tests that consecutive failures keep incrementing the counter.
   */
  public function testFailureCounter(): void {
    $checker = $this->checkerWithResponses([
      new Response(404),
      new Response(500),
      // Network errors count as failures.
      new ConnectException('Connection refused', new Request('GET', 'https://example.com')),
      // 2xx response resets the counter and stamps last_checked.
      new Response(200),
    ]);

    $unpublished = $this->createPromotion();
    $unpublished->setUnpublished()->save();

    $promotion = $this->createPromotion();

    $checker->checkLinks();
    $this->assertSame(1, (int) Promotion::load($promotion->id())->get('failed_check_count')->value);

    // Backdate last_checked so the same promotion qualifies again.
    Promotion::load($promotion->id())->set('last_checked', 0)->save();

    $checker->checkLinks();
    $this->assertSame(2, (int) Promotion::load($promotion->id())->get('failed_check_count')->value);

    // Backdate last_checked so the same promotion qualifies again.
    Promotion::load($promotion->id())->set('last_checked', 0)->save();

    $checker->checkLinks();
    $this->assertSame(3, (int) Promotion::load($promotion->id())->get('failed_check_count')->value);

    // Backdate last_checked so the same promotion qualifies again.
    Promotion::load($promotion->id())->set('last_checked', 0)->save();

    $checker->checkLinks();
    $this->assertSame(0, (int) Promotion::load($promotion->id())->get('failed_check_count')->value);

    // Unpublished item was not checked.
    $reloaded = Promotion::load($unpublished->id());
    $this->assertSame(0, (int) $reloaded->get('last_checked')->value);
  }

  /**
   * Tests that an admin login surfaces a warning when promotions are failing.
   */
  public function testLoginWarnsAdminWhenPromotionsAreFailing(): void {
    $checker = $this->checkerWithResponses([new Response(404)]);
    $this->createPromotion();
    $checker->checkLinks();

    $admin = $this->createUser(['administer search promotions']);
    $this->assertInstanceOf(User::class, $admin);

    $hooks = $this->container->get(PromotionHooks::class);
    assert($hooks instanceof PromotionHooks);
    $hooks->userLogin($admin);

    $messages = $this->container->get(MessengerInterface::class)->messagesByType('warning');
    $this->assertCount(1, $messages);
    $this->assertStringContainsString('1 search promotion', (string) reset($messages));
  }

}
