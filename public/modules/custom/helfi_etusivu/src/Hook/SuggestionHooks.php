<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_etusivu\Search\SearchSuggestionRepository;
use Drupal\views\ViewExecutable;

/**
 * Hooks for the search suggestion entity.
 */
final class SuggestionHooks {

  public function __construct(
    private readonly LanguageManagerInterface $languageManager,
  ) {}

  /**
   * Implements hook_views_pre_view().
   *
   * The exposed language filter of the suggestion view is required, so it
   * always has a language selected. Without a default, that would be the first
   * language of the site for everyone; start from the language of the page
   * instead, which is the one the editor is working in.
   *
   * @phpstan-param array<int, mixed> $args
   */
  #[Hook(hook: 'views_pre_view')]
  public function viewsPreView(ViewExecutable $view, string $displayId, array &$args): void {
    if ($view->id() !== SearchSuggestionRepository::VIEW_ID) {
      return;
    }

    $input = $view->getExposedInput();

    // An editor who picked a language keeps it.
    if (isset($input['langcode'])) {
      return;
    }

    $input['langcode'] = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
    $view->setExposedInput($input);
  }

  /**
   * Implements hook_local_tasks_alter().
   *
   * @phpstan-param array<string, array<string, mixed>> $local_tasks
   */
  #[Hook(hook: 'local_tasks_alter')]
  public function localTasksAlter(array &$local_tasks): void {
    // Content translation hardcodes the canonical route as the base route of
    // the 'Translate' tab. Search suggestions have no canonical route, so the
    // tab is re-rooted to the edit form to keep it visible.
    // @see \Drupal\content_translation\Plugin\Derivative\ContentTranslationLocalTasks
    // @see \Drupal\helfi_etusivu\Entity\Search\Suggestion
    $translate = 'content_translation.local_tasks:entity.helfi_search_suggestion.content_translation_overview';

    if (isset($local_tasks[$translate])) {
      $local_tasks[$translate]['base_route'] = 'entity.helfi_search_suggestion.edit_form';
    }
  }

}
