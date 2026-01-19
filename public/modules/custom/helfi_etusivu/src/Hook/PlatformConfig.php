<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\helfi_platform_config\DTO\ParagraphTypeCollection;

/**
 * Platform config hooks.
 */
final readonly class PlatformConfig {

  /**
   * Implements hook_helfi_paragraph_types().
   */
  #[Hook('helfi_paragraph_types')]
  public function paragraphTypes() : array {
    $entities = [
      'node' => [
        'landing_page' => [
          'field_content' => [
            'current' => 15,
            'front_page_top_news' => 16,
            'front_page_latest_news' => 17,
            'event_list' => 18,
            'news_archive' => 19,
          ],
        ],
        'page' => [
          'field_lower_content' => [
            'front_page_latest_news' => 15,
          ],
        ],
      ],
      'paragraph' => [
        'news_update' => [
          'field_news_update' => [
            'text' => 0,
            'image' => 1,
            'remote_video' => 2,
            'banner' => 3,
          ],
        ],
      ],
    ];

    $enabled = [];
    foreach ($entities as $entityTypeId => $bundles) {
      foreach ($bundles as $bundle => $fields) {
        foreach ($fields as $field => $paragraphTypes) {
          foreach ($paragraphTypes as $paragraphType => $weight) {
            $enabled[] = new ParagraphTypeCollection($entityTypeId, $bundle, $field, $paragraphType, $weight);
          }
        }
      }
    }
    return $enabled;
  }

}
