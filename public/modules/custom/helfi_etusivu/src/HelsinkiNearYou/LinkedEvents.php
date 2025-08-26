<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou;

use Drupal\Core\Url;

/**
 * Linked events helper.
 */
class LinkedEvents {

  public const BASE_URL = 'https://tapahtumat.hel.fi';
  protected const API_URL = 'https://api.hel.fi/linkedevents/v1/';

  /**
   * Form url for getting events from api.
   *
   * @param string $langcode
   *   The language code.
   * @param array $options
   *   Filters as key = value array.
   * @param int $pageSize
   *   How many events to load in a page.
   *
   * @return string
   *   Resulting api url with params a query string
   */
  public function getEventsRequest(string $langcode, array $options = [], int $pageSize = 3) : string {
    $defaultOptions = [
      'event_type' => 'General',
      'format' => 'json',
      'include' => 'keywords,location',
      'page' => 1,
      'page_size' => $pageSize,
      'sort' => 'end_time',
      'start' => 'now',
      'super_event_type' => 'umbrella,none',
      'language' => $langcode,
    ];

    $options = array_merge($defaultOptions, $options);

    if (!isset($options['all_ongoing_AND'])) {
      $options['all_ongoing'] = 'true';
    }

    // Linked events URLs should end with '/' (URLs without '/' are redirect).
    return Url::fromUri(self::API_URL . 'event/', options: ['query' => $options])->toString();
  }

}
