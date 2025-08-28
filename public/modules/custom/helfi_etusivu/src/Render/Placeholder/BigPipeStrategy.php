<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Render\Placeholder;

use Drupal\big_pipe\Render\Placeholder\BigPipeStrategy as CoreBigPipeStrategy;

/**
 * A placeholder strategy to force load content via ajax placeholders.
 *
 * By default, 'Big pipe' module only works for users with an active session.
 *
 * The 'Big pipe sessionless' provides a placeholder strategy for anonymous
 * users, but it loads the HTML response in chunks, and provides no placeholder
 * DOM until the content is fully loaded.
 *
 * We have some very slow loading, highly dynamic content on pages like
 * 'Helsinki near you', where we always want to load content via ajax
 * placeholders.
 *
 * This render_strategy allows certain routes to always load content via ajax
 * placeholders when 'big_pipe_force_placeholders' route option is set to true.
 */
final class BigPipeStrategy extends CoreBigPipeStrategy {

  /**
   * {@inheritdoc}
   */
  public function processPlaceholders(array $placeholders) : array {
    $routeObject = $this->routeMatch->getRouteObject();

    // We want to load some highly dynamic content via ajax
    // placeholders, regardless of the user session.
    if (!$routeObject->getOption('_big_pipe_force_placeholders')) {
      return [];
    }
    $request = $this->requestStack->getCurrentRequest();

    // Prevent placeholders from being processed by BigPipe on uncacheable
    // request methods. For example, a form rendered inside a placeholder will
    // be rendered as soon as possible before any headers are sent, so that it
    // can be detected, submitted, and redirected immediately.
    // @todo https://www.drupal.org/node/2367555
    if (!$request->isMethodCacheable()) {
      return [];
    }

    return $this->doProcessPlaceholders($placeholders);
  }

}
