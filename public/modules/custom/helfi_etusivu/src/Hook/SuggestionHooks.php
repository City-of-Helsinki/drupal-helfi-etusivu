<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hooks for the search suggestion entity.
 */
final class SuggestionHooks {

  /**
   * Implements hook_local_tasks_alter().
   *
   * Content translation hardcodes canonical route as the base route of
   * 'Translate' tab. Suggestions have no canonical route, so this
   * places the tab to the edit form.
   *
   * @see \Drupal\content_translation\Plugin\Derivative\ContentTranslationLocalTasks
   *
   * @phpstan-param array<string, array<string, mixed>> $local_tasks
   */
  #[Hook(hook: 'local_tasks_alter')]
  public function localTasksAlter(array &$local_tasks): void {
    $translate = 'content_translation.local_tasks:entity.helfi_search_suggestion.content_translation_overview';

    if (isset($local_tasks[$translate])) {
      $local_tasks[$translate]['base_route'] = 'entity.helfi_search_suggestion.edit_form';
    }
  }

}
