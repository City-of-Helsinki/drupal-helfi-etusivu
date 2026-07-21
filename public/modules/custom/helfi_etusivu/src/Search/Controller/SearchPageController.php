<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Search\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;

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

    $site_search_config = $this->configFactoryService->get('helfi_search.settings');

    $build = [
      '#theme' => 'helfi_etusivu_site_search',
      '#attached' => [
        'drupalSettings' => [
          'helfi_site_search' => [
            'search_url' => $search_url,
            'external_links' => $site_search_config->get('external_links'),
            'ai_register_url' => $site_search_config->get('ai_register_url'),
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

    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($site_search_config);
    $cache->applyTo($build);

    return $build;
  }

  /**
   * Returns the title.
   */
  public function getTitle(): string {
    return (string) $this->t('Search this site', [], ['context' => 'Site search']);
  }

}
