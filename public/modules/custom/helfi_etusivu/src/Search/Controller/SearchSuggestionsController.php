<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Search\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Language\LanguageInterface;
use Drupal\helfi_etusivu\Search\SearchSuggestionRepository;

/**
 * Serves the sorted search suggestions.
 */
final class SearchSuggestionsController extends ControllerBase {

  use AutowireTrait;

  public function __construct(
    private readonly SearchSuggestionRepository $repository,
  ) {
  }

  /**
   * Lists the suggestions.
   */
  public function content(): CacheableJsonResponse {
    // TYPE_CONTENT, not TYPE_INTERFACE: the drag UI stores the order under the
    // content language, so reading a different one would silently return the
    // wrong order.
    $langcode = $this->languageManager()
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();

    $cacheability = (new CacheableMetadata())
      ->addCacheContexts(['languages:language_content'])
      // Invalidated when a suggestion is created, updated or deleted, and also
      // by draggableviews_views_submit() when the order is saved.
      ->addCacheTags(['helfi_search_suggestion_list'])
      // draggableviews_views_submit() invalidates these two unconditionally on
      // every reorder, so they are the reliable half of the contract.
      ->addCacheTags([
        'config:views.view.' . SearchSuggestionRepository::VIEW_ID,
        'config:views.view.' . SearchSuggestionRepository::VIEW_ID . '.' . SearchSuggestionRepository::VIEW_DISPLAY_ID,
      ]);

    $suggestions = [];
    foreach ($this->repository->getOrdered($langcode) as $suggestion) {
      $suggestions[] = [
        'id' => $suggestion->uuid(),
        'term' => $suggestion->getSuggestion(),
      ];
    }

    $response = new CacheableJsonResponse($suggestions);
    $response->addCacheableDependency($cacheability);
    $response->setPublic();

    return $response;
  }

}
