<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hooks for the Promotion entity edit form.
 */
final class PromotionFormHooks {

  /**
   * Implements hook_gin_content_form_routes().
   *
   * @return string[]
   *   Route names that should use Gin's content-form sidebar layout.
   */
  #[Hook(hook: 'gin_content_form_routes')]
  public function ginContentFormRoutes(): array {
    return [
      'entity.helfi_search_promotion.add_form',
      'entity.helfi_search_promotion.edit_form',
    ];
  }

}
