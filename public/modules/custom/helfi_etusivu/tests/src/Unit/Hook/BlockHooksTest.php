<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\Hook;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\block\Entity\Block;
use Drupal\helfi_etusivu\Hook\BlockHooks;
use Drupal\node\NodeInterface;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests block access logic in BlockHooks.
 *
 * @coversDefaultClass \Drupal\helfi_etusivu\Hook\BlockHooks
 * @group helfi_etusivu
 */
class BlockHooksTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Builds the system under test with sensible defaults.
   */
  private function getSut(
    string $langId = 'fi',
    bool $isFrontPage = FALSE,
    ?string $routeName = NULL,
    ?NodeInterface $node = NULL,
    ?MessengerInterface $messenger = NULL,
  ): BlockHooks {
    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn($langId);

    $languageManager = $this->prophesize(LanguageManagerInterface::class);
    $languageManager->getCurrentLanguage()->willReturn($language->reveal());

    $routeMatch = $this->prophesize(RouteMatchInterface::class);
    $routeMatch->getRouteName()->willReturn($routeName);
    $routeMatch->getParameter('node')->willReturn($node);

    $pathMatcher = $this->prophesize(PathMatcherInterface::class);
    $pathMatcher->isFrontPage()->willReturn($isFrontPage);

    $sut = new BlockHooks(
      $languageManager->reveal(),
      $routeMatch->reveal(),
      $pathMatcher->reveal(),
      $messenger ?? $this->prophesize(MessengerInterface::class)->reveal(),
    );
    $sut->setStringTranslation($this->getStringTranslationStub());
    return $sut;
  }

  /**
   * Creates a mock Block with the given plugin ID.
   */
  private function mockBlock(string $pluginId): Block {
    $block = $this->prophesize(Block::class);
    $block->getPluginId()->willReturn($pluginId);
    return $block->reveal();
  }

  /**
   * Creates a mock node with the given content type bundle.
   */
  private function mockNode(string $bundle): NodeInterface {
    $node = $this->prophesize(NodeInterface::class);
    $node->bundle()->willReturn($bundle);
    $node->getCacheContexts()->willReturn([]);
    $node->getCacheTags()->willReturn([]);
    $node->getCacheMaxAge()->willReturn(-1);
    return $node->reveal();
  }

  /**
   * Creates a mock account.
   */
  private function mockAccount(): AccountInterface {
    return $this->prophesize(AccountInterface::class)->reveal();
  }

  /**
   * The hook must return neutral for non-react_and_share blocks.
   */
  public function testNonReactAndShareBlockIsNeutral(): void {
    $result = $this->getSut()->askemBlockAccess(
      $this->mockBlock('other_block'), 'view', $this->mockAccount()
    );
    $this->assertInstanceOf(AccessResultNeutral::class, $result);
  }

  /**
   * Tests access granted for languages, routes and content types.
   *
   * @dataProvider accessGrantedCases
   */
  public function testAccessGranted(string $langId, ?string $routeName, ?string $nodeBundle): void {
    $node = $nodeBundle ? $this->mockNode($nodeBundle) : NULL;
    $result = $this->getSut(langId: $langId, routeName: $routeName, node: $node)
      ->askemBlockAccess($this->mockBlock('react_and_share'), 'view', $this->mockAccount());
    $this->assertInstanceOf(AccessResultAllowed::class, $result);
  }

  /**
   * Access granted cases.
   *
   * @return array<string, mixed>
   *   Returns array of test cases.
   */
  public static function accessGrantedCases(): array {
    $nearYouRoute = 'helfi_etusivu.helsinki_near_you_results';
    return [
      'fi on helsinki near you'    => ['fi', $nearYouRoute, NULL],
      'en on helsinki near you'    => ['en', $nearYouRoute, NULL],
      'sv on helsinki near you'    => ['sv', $nearYouRoute, NULL],
      'page content type'          => ['fi', NULL, 'page'],
      'landing_page content type'  => ['fi', NULL, 'landing_page'],
    ];
  }

  /**
   * Tests access denied.
   *
   * Tests access denied for languages, the front page, the update
   * operation, unsupported content types, and routes without a node.
   *
   * @dataProvider accessDeniedCases
   */
  public function testAccessDenied(string $operation, string $langId, bool $isFrontPage, ?string $nodeBundle): void {
    $node = $nodeBundle ? $this->mockNode($nodeBundle) : NULL;
    $result = $this->getSut(langId: $langId, isFrontPage: $isFrontPage, node: $node)
      ->askemBlockAccess($this->mockBlock('react_and_share'), $operation, $this->mockAccount());
    $this->assertInstanceOf(AccessResultForbidden::class, $result);
  }

  /**
   * Access denied cases.
   *
   * @return array<string, mixed>
   *   Returns array of test cases.
   */
  public static function accessDeniedCases(): array {
    return [
      'update operation'        => ['update', 'fi', FALSE, NULL],
      'unsupported language ar' => ['view', 'ar', FALSE, NULL],
      'unsupported language it' => ['view', 'it', FALSE, NULL],
      'front page'              => ['view', 'fi', TRUE, NULL],
      'other content type'      => ['view', 'fi', FALSE, 'news_item'],
      'route without a node'    => ['view', 'fi', FALSE, NULL],
    ];
  }

  /**
   * Test cache contexts.
   */
  public function testCacheContextsArePresent(): void {
    $result = $this->getSut()
      ->askemBlockAccess($this->mockBlock('react_and_share'), 'view', $this->mockAccount());
    $this->assertInstanceOf(CacheableDependencyInterface::class, $result);
    assert($result instanceof CacheableDependencyInterface);
    $this->assertContains('languages:language_interface', $result->getCacheContexts());
    $this->assertContains('url.path', $result->getCacheContexts());
    $this->assertContains('url.path.is_front', $result->getCacheContexts());
  }

  /**
   * Visiting the block layout page must trigger the admin warning message.
   */
  public function testAdminWarningIsShown(): void {
    $messenger = $this->prophesize(MessengerInterface::class);
    $messenger->addWarning(Argument::any())->shouldBeCalledOnce();

    $this->getSut(messenger: $messenger->reveal())->formBlockAdminDisplayFormAlter();
  }

}
