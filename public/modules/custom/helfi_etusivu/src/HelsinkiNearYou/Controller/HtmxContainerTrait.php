<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Htmx\Htmx;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * A trait to manage lazy builder items.
 */
trait HtmxContainerTrait {

  /**
   * Constructs a render array for events.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int|null $limit
   *   The number of items to show.
   *
   * @return array
   *   The events render array.
   */
  protected function buildEventsHtmxContainer(Request $request, ?int $limit = NULL) : array {
    return $this->buildHtmxRenderArray(
      'helfi_etusivu.helsinki_near_you_events_htmx',
      $request,
      $limit,
    );
  }

  /**
   * Constructs a HTMX render array.
   *
   * @param string $route
   *   The htmx route.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int|null $limit
   *   The limit.
   * @param \Drupal\Core\Template\Attribute|null $attributes
   *   The preview attributes or null.
   *
   * @return array
   *   The htmx render array.
   */
  protected function buildHtmxRenderArray(string $route, Request $request, ?int $limit = NULL, ?Attribute $attributes = NULL): array {
    $htmx = new Htmx();
    $htmx->get(new Url($route, options: [
      'query' => array_merge(['limit' => $limit], $request->query->all()),
    ]))
      ->trigger('load');

    $build = [
      '#theme' => 'helsinki_near_you_lazy_builder_preview',
      '#num_items' => $limit,
      '#preview_attributes' => $attributes,
    ];
    $htmx->applyTo($build);
    return $build;
  }

  /**
   * Constructs a render array for feedback items.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int|null $limit
   *   The item limit.
   *
   * @return array
   *   The render array.
   */
  protected function buildFeedbackHtmxContainer(Request $request, ?int $limit = NULL) : array {
    return $this->buildHtmxRenderArray(
      'helfi_etusivu.helsinki_near_you_feedback_htmx',
      $request,
      $limit,
    );
  }

  /**
   * Constructs a render array for roadwork items.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int|null $limit
   *   The number of items to show.
   *
   * @return array
   *   The render array.
   */
  protected function buildRoadworksHtmxContainer(Request $request, ?int $limit = NULL) : array {
    return $this->buildHtmxRenderArray(
      'helfi_etusivu.helsinki_near_you_roadworks_htmx',
      $request,
      $limit,
      new Attribute([
        'class' => ['card--ghost--no-image'],
      ]),
    );
  }

}
