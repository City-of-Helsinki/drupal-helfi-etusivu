<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\Block;

use Drupal\helfi_navigation\ExternalMenuTree;
use Drupal\helfi_navigation\Menu\Menu;
use Drupal\helfi_navigation\Plugin\Block\ExternalMenuBlockBase;

/**
 * Provides an external menu block to etusivu.
 *
 * @Block(
 *   id = "local_external_menu_block",
 *   admin_label = @Translation("Local external menu block"),
 *   category = @Translation("Local external menu"),
 *   deriver = "Drupal\helfi_navigation\Plugin\Derivative\ExternalMenuBlock"
 * )
 */
class LocalExternalMenuBlock extends ExternalMenuBlockBase {

  /**
   * Build external menu render array.
   *
   * @return array|null
   *   Returns the render array.
   */
  public function build():? array {
    $build = [];

    $local_menu_service = \Drupal::service('helfi_global_navigation.menu_response_handler');
    $current_language_id = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $menu_type = $this->getDerivativeId();
    $menu_tree = $this->buildFromJson(
      $local_menu_service->getMenuResponse(
        Menu::MAIN_MENU,
        $current_language_id
      )
    );

    // @todo UHF-5828: local external menu block.
    // @todo Remember to invalidate cache tag when menu post-endpoint is triggered.
    // @todo Test cache context.
    if ($menu_tree instanceof ExternalMenuTree) {
      $build['#cache'] = [
        'cache_context' => $this->getCacheContexts(),
        'cache_tags' => $this->getCacheTags(),
      ];

      $build['#sorted'] = TRUE;
      $build['#items'] = $menu_tree->getTree();
      $build['#theme'] = 'menu__external_menu';
      $build['#menu_type'] = $menu_type;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // @todo: If you want to make other menu blocks.
    // instead of :main use $this->getDerivativeId()
    return [
      "config:system.menu.main",
    ];
  }

}
