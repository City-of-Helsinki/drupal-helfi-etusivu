<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Block;

use Drupal\helfi_users\Plugin\Block\LocalTasksBlock;

/**
 * Class to override core's LocalTaskBlock.
 *
 * Based on other languages and UHF-8395
 * Temporary workaround for drupal core issue 3054641.
 * Translate the local tasks menu's link titles on preferred admin language.
 */
class EtusivuLocalTasksBlock extends LocalTasksBlock {

  /**
   * {@inheritDoc}
   *
   * @phpstan-return array<mixed>
   */
  public function build(): array {
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
