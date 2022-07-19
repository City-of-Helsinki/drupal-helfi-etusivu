<?php

declare(strict_types=1);

namespace Drupal\helfi_global_navigation;

use Drupal\Core\State\State;

class MenuCache {

  public function __construct(private State $state){
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
  public function getCached(string $menu_type, string $lang_code): ?array{
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
   *
   * @return void
   */
  public function setCache(string $menu_type, string $lang_code, mixed $data) {
    $this->state->set(sprintf('%s_%s', $menu_type, $lang_code), $data);
  }

}
