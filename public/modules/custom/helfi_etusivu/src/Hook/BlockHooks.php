<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\block\Entity\Block;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

/**
 * Hook implementations for blocks.
 */
class BlockHooks {

  use AutowireTrait;
  use StringTranslationTrait;

  public function __construct(
    private readonly LanguageManagerInterface $languageManager,
    private readonly RouteMatchInterface $routeMatch,
    private readonly PathMatcherInterface $pathMatcher,
    private readonly MessengerInterface $messenger,
  ) {
  }

  /**
   * Implements hook_block_access().
   */
  #[Hook('block_access')]
  public function askemBlockAccess(Block $block, string $operation, AccountInterface $account): AccessResultInterface {
    // Only applies to the React and Share block.
    if ($block->getPluginId() !== 'react_and_share') {
      return AccessResult::neutral();
    }

    $cacheContexts = ['languages:language_interface', 'url.path', 'url.path.is_front'];

    // Prevent editing the block configuration via the UI.
    if ($operation === 'update') {
      return AccessResult::forbidden()->addCacheContexts($cacheContexts);
    }

    // Only control view access from here on.
    if ($operation !== 'view') {
      return AccessResult::neutral();
    }

    // Only show on Finnish, English and Swedish.
    $language = $this->languageManager->getCurrentLanguage()->getId();
    if (!in_array($language, ['fi', 'en', 'sv'], TRUE)) {
      return AccessResult::forbidden()->addCacheContexts($cacheContexts);
    }

    // Hide on the front page.
    if ($this->pathMatcher->isFrontPage()) {
      return AccessResult::forbidden()->addCacheContexts($cacheContexts);
    }

    // Show on Helsinki near you pages.
    $routes = [
      'helfi_etusivu.helsinki_near_you_results',
      'helfi_etusivu.helsinki_near_you_feedback',
      'helfi_etusivu.helsinki_near_you_events',
      'helfi_etusivu.helsinki_near_you_roadworks',
    ];
    if (in_array($this->routeMatch->getRouteName(), $routes)) {
      return AccessResult::allowed()->addCacheContexts($cacheContexts);
    }

    // Show on page and landing_page content types.
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface && $node->bundle() === 'page') {
      return AccessResult::allowed()
        ->addCacheContexts($cacheContexts)
        ->addCacheableDependency($node);
    }

    return AccessResult::forbidden()->addCacheContexts($cacheContexts);
  }

  /**
   * Implements hook_form_block_alter().
   */
  #[Hook('form_block_admin_display_form_alter')]
  public function formBlockAdminDisplayFormAlter(): void {
    $this->messenger->addWarning($this->t('Askem (React and share) block cannot be modified from the UI. See \Drupal\helfi_etusivu\Hook\BlockHooks::askemBlockAccess().'));
  }

}
