<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\NearYouForm;
use Drupal\helfi_etusivu\HelsinkiNearYou\ServiceMapInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A controller to list feedback for given coordinates.
 */
final class FeedbacksController extends ControllerBase {

  use FeedbackTrait;

  public function __construct(private ServiceMapInterface $serviceMap, FormBuilderInterface $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

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
      '#cache' => [
        'contexts' => ['url.query_args:q'],
        'tags' => ['feedbacks_section'],
      ],
      'autosuggest_form' => $this->formBuilder()
        ->getForm(NearYouForm::class, 'helfi_etusivu.helsinki_near_you_feedbacks'),
    ];

    $address = $request->query->get('q', '');
    $addressData = $this->serviceMap->getAddressData(urldecode($address));

    if (!$addressData) {
      $this->messenger()->addError(
        $this->t(
          'Make sure the address is written correctly. You can also search using a nearby street number.',
          [],
          ['context' => 'React search: Address not found hint']
        )
      );

      return $build;
    }
    [$lon, $lat] = $addressData['coordinates'];

    if ($lat && $lon) {
      $build['feedback'] = $this->buildFeedback(
          (float) $lon,
          (float) $lat,
        );
      return $build;
    }

    return $build;
  }

}
