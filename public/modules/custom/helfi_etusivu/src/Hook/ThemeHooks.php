<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementations.
 */
final class ThemeHooks {

  /**
   * Implements hook_theme().
   *
   * @phpstan-return array<string, mixed>
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'helsinki_near_you_landing_page' => [
        'variables' => [
          'title' => NULL,
          'description' => NULL,
          'illustration' => NULL,
          'illustration_url_1x' => NULL,
          'illustration_url_2x' => NULL,
          'illustration_caption' => NULL,
        ],
        'template' => 'helsinki-near-you-landing-page',
      ],
      'helsinki_near_you_roadwork_section' => [
        'variables' => [
          'title' => NULL,
          'projects' => [],
        ],
        'template' => 'helsinki-near-you-roadwork-section',
      ],
      'helsinki_near_you_roadworks' => [
        'variables' => [
          'title' => NULL,
          'roadworks_data' => NULL,
          'address' => NULL,
        ],
        'template' => 'helsinki-near-you-roadworks',
      ],
      'helsinki_near_you_roadwork_item' => [
        'variables' => [
          'title' => NULL,
          'uri' => NULL,
          'work_type' => NULL,
          'address' => NULL,
          'schedule' => NULL,
          'distance_label' => NULL,
          'limit' => NULL,
        ],
        'template' => 'helsinki-near-you-roadwork-item',
      ],
      'helsinki_near_you_results_page' => [
        'variables' => [
          'coordinates' => NULL,
          'title' => NULL,
          'toc_enabled' => NULL,
          'toc_title' => NULL,
          'service_groups' => NULL,
          'nearby_neighbourhoods' => NULL,
          'news_archive_url' => NULL,
          'roadwork_section' => NULL,
          'roadwork_archive_url' => NULL,
          'feedback_section' => NULL,
          'feedback_archive_url' => NULL,
          'events_section' => NULL,
          'events_archive_url' => NULL,
        ],
        'template' => 'helsinki-near-you-results-page',
      ],
      'helsinki_near_you_event_item' => [
        'variables' => [
          'title' => NULL,
          'object' => NULL,
          'external_image' => NULL,
        ],
        'template' => 'helsinki-near-you-event-item',
      ],
      'helsinki_near_you_feedback_item' => [
        'variables' => [
          'status' => NULL,
          'description' => NULL,
          'uri' => NULL,
          'title' => NULL,
          'address' => NULL,
          'distance_label' => NULL,
          'requested_datetime' => NULL,
          'limit' => NULL,
        ],
        'template' => 'helsinki-near-you-feedback-item',
      ],
      'helsinki_near_you_events' => [
        'variables' => [],
        'template' => 'helsinki-near-you-events',
      ],
      'helsinki_near_you_hero_block' => [
        'variables' => [
          'hero_title' => NULL,
          'hero_description' => NULL,
          'first_paragraph_bg' => NULL,
          'form' => [],
        ],
        'template' => 'helsinki-near-you-hero-block',
      ],
      'helsinki_near_you_search_page' => [
        'variables' => [
          'autosuggest_form' => NULL,
          'content' => NULL,
          'content_attributes' => ['classes' => []],
          'component_title' => NULL,
          'component_description' => NULL,
          'address_missing_message' => NULL,
          'address_error_message' => NULL,
        ],
      ],
      'helsinki_near_you_lazy_builder_content' => [
        'variables' => [
          'title' => NULL,
          'content' => NULL,
          'has_error' => FALSE,
        ],
      ],
      'helsinki_near_you_lazy_builder_preview' => [
        'variables' => [
          'attributes' => NULL,
          'num_items' => NULL,
          'preview_attributes' => NULL,
          'searching_label' => NULL,
        ],
      ],
      'news_item_unpublish_hint' => [
        'variables' => [
          'message' => NULL,
        ],
      ],
      'helfi_etusivu_site_search' => [
        'variables' => [],
        'template' => 'helfi-etusivu-site-search',
      ],
      'helfi_search_promotion' => [
        'render element' => 'elements',
        'template' => 'helfi-search-promotion',
      ],
    ];
  }

}
