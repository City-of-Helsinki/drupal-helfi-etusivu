<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Language;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Url;
use Drupal\helfi_api_base\Language\DefaultLanguageResolverInterface;

/**
 * Handler for alternate language fallback service.
 */
final class AltLanguageFallbacks {

  /**
   * Fallback regions to add language and direction attributes to.
   *
   * @var array
   */
  protected array $fallbackRegions = [
    'header_top',
    'header_bottom',
    'header_branding',
    'footer_top',
    'footer_bottom',
  ];

  /**
   * Fallback blocks to add language and direction attributes to.
   *
   * @var array
   */
  protected array $fallbackBlocks = [
    'menu_block_current_language:header-top-navigation',
    'menu_block_current_language:footer-top-navigation',
    'menu_block_current_language:footer-top-navigation-2',
    'menu_block_current_language:footer-bottom-navigation',
  ];

  /**
   * Fallback menus to regenerate menu trees for.
   *
   * @var array
   */
  protected array $fallbackMenus = [
    'header-top-navigation',
    'footer-top-navigation',
    'footer-top-navigation-2',
    'footer-bottom-navigation',
  ];

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    private MenuLinkTreeInterface $menuTree,
    private DefaultLanguageResolverInterface $defaultLanguageResolver,
  ) {
  }

  /**
   * Checks if region has fallback language content.
   *
   * Currently only determined by region name.
   *
   * @param string $region_name
   *   Region name to check.
   *
   * @return bool
   *   Returns TRUE if we assume that region has fallback content.
   */
  public function shouldAttributesBeAddedToRegion(string $region_name): bool {
    // Only act on alternative languages.
    if (!$this->defaultLanguageResolver->isAltLanguage()) {
      return FALSE;
    }

    return in_array($region_name, $this->fallbackRegions);
  }

  /**
   * Checks if block can have fallback language content.
   *
   * Current language parameters are added if content is translated.
   *
   * @param string $plugin_id
   *   Block plugin ID to check.
   *
   * @return bool
   *   Returns TRUE if block can have fallback content.
   */
  public function shouldAttributesBeAddedToBlock(string $plugin_id): bool {
    // Only act on alternative languages.
    if (!$this->defaultLanguageResolver->isAltLanguage()) {
      return FALSE;
    }

    return in_array($plugin_id, $this->fallbackBlocks);
  }

  /**
   * Checks if block has has fallback language content.
   *
   * @param array $variables
   *   Block preprocess variables.
   *
   * @return bool
   *   Returns TRUE if block has menus with fallback content.
   */
  public function checkIfBlockHasFallbackContent(array $variables): bool {
    // Only act on alternative languages.
    if (!$this->defaultLanguageResolver->isAltLanguage()) {
      return FALSE;
    }

    // Check if menu has fallback content.
    if (empty($variables['content'])) {
      return FALSE;
    }

    if (empty($variables['content']['#menu_name']) || empty($variables['content']['#items'])) {
      return FALSE;
    }

    return $this->shouldMenuTreeBeReplaced($variables['content']['#menu_name'], $variables['content']['#items']);
  }

  /**
   * Checks if menu tree should be replaced by this service.
   *
   * @param string $menu_name
   *   Menu tree to check.
   * @param array $items
   *   Current menu items to check.
   *
   * @return bool
   *   Returns TRUE if menu should be handled by this module.
   */
  public function shouldMenuTreeBeReplaced(string $menu_name, array $items): bool {
    // Only act on alternative languages.
    if (!$this->defaultLanguageResolver->isAltLanguage()) {
      return FALSE;
    }

    // Only act on specific menus.
    if (!in_array($menu_name, $this->fallbackMenus)) {
      return FALSE;
    }

    // Only act if menu has one <nolink> placeholder element.
    if (empty($items) || count($items) > 1) {
      return FALSE;
    }

    $item = array_shift($items);
    if (!isset($item['url']) || !$item['url'] instanceof Url) {
      return FALSE;
    }

    if ($item['url']->isRouted() && $item['url']->getRouteName() === '<nolink>') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Replace menu tree with default language links.
   *
   * @param string $menu_name
   *   Name of the rerendered menu.
   *
   * @return array
   *   Render array for rerendered menu tree.
   */
  public function replaceMenuTree(string $menu_name): array {
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    $tree = $this->menuTree->load($menu_name, $parameters);

    foreach ($tree as $key => $element) {
      /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $link */
      $link = $element->link;
      // Only support menu_link_content elements for now.
      if ($link->getProvider() !== 'menu_link_content') {
        continue;
      }

      $metadata = $link->getMetadata();
      $entity = $this->entityTypeManager->getStorage('menu_link_content')->load($metadata['entity_id']);
      /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $entity */
      if ($entity->get('langcode')->value !== $this->defaultLanguageResolver->getFallbackLanguage()) {
        unset($tree[$key]);
      }
    }

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    $tree = $this->menuTree->transform($tree, $manipulators);
    $menu = $this->menuTree->build($tree);

    if (!empty($menu['#items'])) {
      return $menu['#items'];
    }

    return [];
  }

}
