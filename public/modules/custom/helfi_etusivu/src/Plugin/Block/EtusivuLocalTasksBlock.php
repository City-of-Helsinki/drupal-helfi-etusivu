<?php
declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Block;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\Plugin\Block\LocalTasksBlock;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class to override core's LocalTaskBlock.
 *
 * Based on other languages and UHF-8395
 * Temporary workaround for drupal core issue 3054641.
 * Translate the local tasks menu's link titles on preferred admin language.
 */
class EtusivuLocalTasksBlock extends LocalTasksBlock {

  /**
   * Language-manager interface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->languageManager = $container->get('language_manager');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function build() {
    $build = parent::build();

    if (!isset($build['#primary']) || !is_array($build['#primary'])) {
      return $build;
    }

    $adminLanguage = $this->currentUser->getPreferredAdminLangcode();

    $routes = [
      'entity.node.canonical' => 'View',
      'entity.node.edit_form' => 'Edit',
      'entity.node.delete_form' => 'Delete',
      'entity.node.version_history' => 'Revisions',
      'entity.node_type.edit_form' => 'Edit',
      'entity.node_type.collection' => 'List',
      'content_translation.local_tasks:entity.node.content_translation_overview' => 'Translate',
    ];

    foreach (array_keys($build['#primary']) as $route) {
      if (isset($build['#primary'][$route]) && isset($routes[$route])) {
        // phpcs:ignore
        $build['#primary'][$route]['#link']['title'] = $this->t($routes[$route], [], ['langcode' => $adminLanguage]);
      }
    }

    $build['#cache']['contexts'][] = 'user';
    return $build;
  }

}
