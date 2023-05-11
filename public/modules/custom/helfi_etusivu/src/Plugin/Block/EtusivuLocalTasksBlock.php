<?php

namespace Drupal\helfi_etusivu\Plugin\Block;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\Plugin\Block\LocalTasksBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class to override core's LocalTaskBlock.
 */
class EtusivuLocalTasksBlock extends LocalTasksBlock {

  /**
   * Language-manager interface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->defaultLanguageResolver = $container->get('helfi_api_base.default_language_resolver');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function build() {
    $build = parent::build();

    if (!is_array($build['#primary'])) {
      return $build;
    }

    $adminLanguage = \Drupal::currentUser()->getPreferredAdminLangcode();

    $translations = [
      'entity.node.canonical' => 'View',
      'entity.node.edit_form' => 'Edit',
      'entity.node.delete_form' => 'Delete',
      'entity.node.version_history' => 'Revisions',
      'entity.node_type.edit_form' => 'Edit',
      'entity.node_type.collection' => 'List',
      'content_translation.local_tasks:entity.node.content_translation_overview' => 'Translate',
    ];

    foreach (array_keys($build['#primary']) as $key) {
      if ($build['#primary'][$key] && $translations[$key]) {
        // phpcs:ignore
        $build['#primary'][$key]['#link']['title'] = $this->t($translations[$key], [], ['langcode' => $adminLanguage]);
      }
    }

    $build['#cache']['contexts'][] = 'user';
    return $build;
  }

}
