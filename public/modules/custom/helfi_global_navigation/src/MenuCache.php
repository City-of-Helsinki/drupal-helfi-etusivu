<?php

declare(strict_types=1);

namespace Drupal\helfi_global_navigation;

use Drupal\Core\State\State;

/**
 * Caching for menu request response.
 */
class MenuCache {

  /**
   * Construct.
   *
   * @param \Drupal\Core\State\State $state
   *   State.
   */
  public function __construct(private State $state) {
  }

  /**
   * Get cached response.
   *
   * @param string $menu_type
   *   Menu type.
   * @param string $lang_code
   *   Language code.
   *
   * @return array|null
   *   Cached response
   */
  public function getCached(string $menu_type, string $lang_code): ?array {
    return $this->state->get(sprintf('%s_%s', $menu_type, $lang_code));
  }

  /**
   * Set menu response to cache.
   *
   * @param string $menu_type
   *   Menu type.
   * @param string $lang_code
   *   Language code.
   * @param mixed $data
   *   Menu response data.
   */
  public function setCache(string $menu_type, string $lang_code, mixed $data): void {
    $this->state->set(sprintf('%s_%s', $menu_type, $lang_code), $data);
  }

}
