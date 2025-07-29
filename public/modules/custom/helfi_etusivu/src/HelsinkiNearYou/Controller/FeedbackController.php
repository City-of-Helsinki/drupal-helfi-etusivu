<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * A controller to list feedback for given coordinates.
 */
final class FeedbackController extends ControllerBase {

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
        'tags' => ['feedback_section'],
      ],
    ];
    return $build + $this->buildFeedback(
      (float) $request->get('lon', 0.0),
      (float) $request->get('lat', 0.0)
    );
  }

}
