<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;

/**
 * Linked events helper.
 */
class LinkedEvents {

  public const BASE_URL = 'https://tapahtumat.hel.fi';
  protected const API_URL = 'https://api.hel.fi/linkedevents/v1/';

  public function __construct(private readonly LanguageManagerInterface $languageManager) {}

  /**
   * Form url for getting events from api.
   *
   * @param array $options
   *   Filters as key = value array.
   * @param string $pageSize
   *   How many events to load in a page.
   *
   * @return string
   *   Resulting api url with params a query string
   */
  public function getEventsRequest(array $options = [], string $pageSize = '3') : string {
    $defaultOptions = [
      'event_type' => 'General',
      'format' => 'json',
      'include' => 'keywords,location',
      'page' => 1,
      'page_size' => $pageSize,
      'sort' => 'end_time',
      'start' => 'now',
      'super_event_type' => 'umbrella,none',
      'language' => $this->languageManager
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId(),
    ];

    $options = array_merge($defaultOptions, $options);

    if (!isset($options['all_ongoing_AND'])) {
      $options['all_ongoing'] = 'true';
    }

    // Linked events URLs should end with '/' (URLs without '/' are redirect).
    return Url::fromUri(self::API_URL . 'event/', options: ['query' => $options])->toString();
  }

}
