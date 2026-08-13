<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Search\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\helfi_api_base\GlobalUrls;

/**
 * Site search controller.
 */
final class SearchPageController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Constructs a new instance.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactoryService,
  ) {
  }

  /**
   * Returns a renderable array.
   *
   * @phpstan-return array<string, mixed>
   */
  public function content(): array {
    $sentry_dsn = $this->configFactoryService
      ->get('react_search.settings')
      ->get('sentry_dsn_react') ?? '';

    $search_url = Url::fromRoute('helfi_search.semantic_search')->toString();

    $thresholds = $this->configFactoryService
      ->get('helfi_search.settings')
      ->get('search_relevance_thresholds') ?? [];

    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    $urls = GlobalUrls::get($langcode);
    $external_links = [
      'jobs' => $urls['jobs_link_url'],
      'events' => $urls['events_link_url'],
      'decisions' => $urls['decisions_link_url'],
      'contact' => $urls['contact_link_url'],
      'helsinki_near_you' => $urls['helsinki_near_you_link_url'],
    ];

    return [
      '#theme' => 'helfi_etusivu_site_search',
      '#attached' => [
        'drupalSettings' => [
          'helfi_site_search' => [
            'search_url' => $search_url,
            'external_links' => $external_links,
            'ai_register_url' => $urls['ai_register_url'],
            'search_relevance_thresholds' => [
              'low' => (float) ($thresholds['low'] ?? 0),
              'medium' => (float) ($thresholds['medium'] ?? 0),
              'high' => (float) ($thresholds['high'] ?? 0),
            ],
          ],
          'helfi_react_search' => [
            'sentry_dsn_react' => $sentry_dsn,
          ],
        ],
        'library' => [
          'hdbt_subtheme/site-search',
        ],
        // @todo Prevent search engines from indexing search page until
        // we are redy to replace the production search page.
        'http_header' => [
          ['X-Robots-Tag', 'noindex'],
        ],
      ],
    ];
  }

  /**
   * Returns the title.
   */
  public function getTitle(): string {
    return (string) $this->t('Search this site', [], ['context' => 'Site search']);
  }

}
