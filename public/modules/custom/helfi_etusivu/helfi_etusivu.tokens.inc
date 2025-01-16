<?php

/**
 * @file
 * Contains token data for helfi_etusivu.
 */

declare(strict_types=1);

use Drupal\helfi_etusivu\Entity\Node\NewsItem;

/**
 * Implements hook_token_info().
 */
function helfi_etusivu_token_info(): array {
  $info = [];

  $info['types']['node']['updating-news:changed:html_datetime'] = [
    'name' => 'updating-news',
    'description' => 'Get the latest changed time of updating news',
    'needs-data' => 'node',
  ];

  $info['tokens']['node']['updating-news:changed:html_datetime'] = [
    'name' => 'Updating news latest update',
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function helfi_etusivu_tokens(
  $type,
  $tokens,
  array $data,
): array {
  if ($type !== 'updating-news' || empty($data['node'])) {
    return [];
  }

  /** @var \Drupal\Core\Datetime\DateFormatterInterface $dateFormatterService */
  $dateFormatterService = \Drupal::service('date.formatter');

  $replacements = [];
  $entity = $data['node'];

  foreach ($tokens as $original) {
    if (
      !$entity instanceof NewsItem ||
      $original !== '[updating-news:changed:html_datetime]'
    ) {
      continue;
    }

    $updates = $entity->getNewsUpdates();
    $latest = end($updates);
    // @phpstan-ignore-next-line
    $timestamp = $latest->get('field_news_update_date')
      ->date
      ->getTimestamp();

    $datetime = $dateFormatterService->format($timestamp, 'html_datetime', 'c');

    $replacements[$original] = $datetime;
  }

  return $replacements;
}
