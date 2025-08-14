<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\FeedbacksSearchForm;
use Drupal\helfi_etusivu\HelsinkiNearYou\ServiceMapInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A controller to list feedback for given coordinates.
 */
final class FeedbacksController extends ControllerBase {

  use LazyBuilderTrait;

  public function __construct(
    private ServiceMapInterface $serviceMap,
    FormBuilderInterface $formBuilder,
    MessengerInterface $messenger,
  ) {
    $this->formBuilder = $formBuilder;
    $this->messenger = $messenger;
  }

  /**
   * A controller callback for feedback route that provides the route title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated route title.
   */
  public function getTitle() {
    return $this->t('Search feedback near you', [], ['context' => 'Helsinki near you title']);
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
      '#theme' => 'helsinki_near_you_feedback_page',
      '#cache' => [
        'contexts' => ['url.query_args:q'],
        'tags' => ['feedbacks_section'],
      ],
      '#autosuggest_form' => $this->formBuilder
        ->getForm(FeedbacksSearchForm::class),
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
    $build['feedback'] = $this->buildFeedback(
      $addressData->location,
      50,
    );

    return $build;
  }

}
