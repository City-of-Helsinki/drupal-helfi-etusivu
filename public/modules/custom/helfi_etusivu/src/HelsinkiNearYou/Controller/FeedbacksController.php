<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * A controller to list feedback for given coordinates.
 */
final class FeedbacksController extends ControllerBase {

  use FeedbackTrait;

  /**
   * A controller callback for feedback route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array[]
   *   The render array.
   */
  public function content(Request $request) : array {
    $build = [
      // @todo Add back button.
      '#cache' => [
        'contexts' => ['url.query_args:lat', 'url.query_args:lon', 'url.query_args:q'],
        'tags' => ['feedbacks_section'],
      ],
    ];

    $lat = $request->query->get('lat');
    $lon = $request->query->get('lon');

    if ($lat && $lon) {
      return $build + $this->buildFeedback(
          (float) $lon,
          (float) $lat,
        );
    }
    return $build + [
      '#theme' => 'helsinki_near_you_landing_page',
      '#title' => $this->t('Search feedbacks by entering your street address', [], ['context' => 'Helsinki near you']),
    ];

  }

}
