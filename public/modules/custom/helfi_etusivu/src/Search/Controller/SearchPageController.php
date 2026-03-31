<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Search\Controller;

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
   */
  public function content(): array {
    $sentry_dsn = $this->configFactoryService
      ->get('react_search.settings')
      ->get('sentry_dsn_react') ?? '';

    $search_url = Url::fromRoute('helfi_search.semantic_search')->toString();

    return [
      '#theme' => 'helfi_etusivu_site_search',
      '#attached' => [
        'drupalSettings' => [
          'helfi_site_search' => [
            'search_url' => $search_url,
          ],
          'helfi_react_search' => [
            'sentry_dsn_react' => $sentry_dsn,
          ],
        ],
        'library' => [
          'hdbt_subtheme/site-search',
        ],
      ],
    ];
  }

}
