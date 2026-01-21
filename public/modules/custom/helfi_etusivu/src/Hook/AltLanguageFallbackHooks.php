<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\helfi_api_base\Language\DefaultLanguageResolverInterface;
use Drupal\helfi_etusivu\Language\AltLanguageFallbacks;

/**
 * Alt language fallback hooks.
 */
final class AltLanguageFallbackHooks {

  public function __construct(
    private DefaultLanguageResolverInterface $defaultLanguageResolver,
    private AltLanguageFallbacks $altLanguageFallbacks,
  ) {
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook(hook: 'preprocess_region')]
  public function preprocessRegion(array &$variables): void {
    if ($this->altLanguageFallbacks->shouldAttributesBeAddedToRegion($variables['region'])) {
      $attributes = $this->defaultLanguageResolver->getFallbackLangAttributes();
      $variables['attributes'] = array_replace($variables['attributes'], $attributes);
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook(hook: 'preprocess_block')]
  public function preprocessBlock(array &$variables): void {
    if (empty($variables['plugin_id'])) {
      return;
    }

    // Check if block is in the list to be altered.
    if (!$this->altLanguageFallbacks->shouldAttributesBeAddedToBlock($variables['plugin_id'])) {
      return;
    }
    // Check if block has fallback content. Otherwise, add current
    // lang attributes.
    if ($this->altLanguageFallbacks->checkIfBlockHasFallbackContent($variables)) {
      $attributes = $this->defaultLanguageResolver->getFallbackLangAttributes();
    }
    else {
      $attributes = $this->defaultLanguageResolver->getCurrentLangAttributes();
    }

    // Add either fallback or current language attributes.
    $variables['attributes'] = array_replace($variables['attributes'], $attributes);
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook(hook: 'preprocess_menu')]
  public function preprocessMenu(array &$variables): void {
    if (empty($variables['menu_name']) || empty($variables['items'])) {
      return;
    }
    if (!$this->altLanguageFallbacks->shouldMenuTreeBeReplaced($variables['menu_name'], $variables['items'])) {
      return;
    }

    $variables['items'] = $this->altLanguageFallbacks->replaceMenuTree($variables['menu_name']);
  }

}

