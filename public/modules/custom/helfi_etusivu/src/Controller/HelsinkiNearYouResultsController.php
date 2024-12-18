<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\Enum\InternalSearchLink;
use Drupal\helfi_etusivu\Enum\ServiceMapLink;
use Drupal\helfi_etusivu\Servicemap;
use Drupal\helfi_react_search\LinkedEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Helsinki near you controller.
 */
class HelsinkiNearYouResultsController extends ControllerBase {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_etusivu\Servicemap $servicemap
   *   The servicemap service.
   * @param \Drupal\helfi_react_search\LinkedEvents $linkedEvents
   *   The linked events service.
   */
  public function __construct(
    protected readonly Servicemap $servicemap,
    protected readonly LinkedEvents $linkedEvents,
  ) {
  }

  /**
   * Returns a renderable array.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   *   The request.
   *
   * @return array
   *   A renderable array.
   */
  public function content(Request $request) : array|RedirectResponse {
    $address = $request->query->get('q');
    $return_url = Url::fromRoute('helfi_etusivu.helsinki_near_you');

    if (!$address) {
      $this->messenger()->addError($this->t('Please enter an address', [], ['context' => 'Helsinki near you']));
      return $this->redirect('helfi_etusivu.helsinki_near_you');
    }
    $address = Xss::filter($address);
    $addressData = $this->getCoordinates(urldecode($address));

    if (!$addressData) {
      $this->messenger()->addError(
        $this->t(
          'The address you input yielded no results. You may want to try a different address.',
          [],
          ['context' => 'Helsinki near you']
        )
      );
      return $this->redirect('helfi_etusivu.helsinki_near_you');
    }

    $addressName = $this->resolveTranslation($addressData['address_translations']);

    return [
      '#attached' => [
        'drupalSettings' => [
          'helfi_events' => [
            'baseUrl' => LinkedEvents::BASE_URL,
            'data' => [
              'helfi-coordinates-based-event-list' => [
                'events_api_url' => $this->linkedEvents->getEventsRequest([
                  'dwithin_origin' => implode(',', $addressData['coordinates']),
                  'dwithin_metres' => 2000,
                ]),
                'field_event_count' => 3,
              ],
            ],
            'seeAllButtonOverride' => $this->t('See all events', [], ['context' => 'Helsinki near you']),
            'useExperimentalGhosts' => TRUE,
          ],
        ],
        'library' => ['hdbt/event-list'],
      ],
      '#back_link_label' => $this->t('Edit address', [], ['context' => 'Helsinki near you']),
      '#back_link_url' => $return_url,
      '#cache' => [
        'contexts' => ['url.query_args:q'],
      ],
      '#coordinates' => $addressData['coordinates'],
      '#theme' => 'helsinki_near_you_results_page',
      '#title' => $this->t(
        'Services, events and news near your address @address',
        ['@address' => $addressName],
        ['context' => 'Helsinki near you']
      ),
      '#service_groups' => [
        [
          'title' => $this->t('Health is key', [], ['context' => 'Helsinki near you']),
          'service_links' => [
            [
              'link_label' => $this->t('Your own health station', [], ['context' => 'Helsinki near you']),
              'link_url' => $this->getInternalSearchLink(
                InternalSearchLink::HEALTH_STATIONS,
                $addressName,
              ),
            ],
            [
              'link_label' => $this->t('Closest maternity and child health clinic', [], ['context' => 'Helsinki near you']),
              'link_url' => $this->getInternalSearchLink(
                InternalSearchLink::CHILD_HEALTH_STATIONS,
                $addressName,
              ),
            ],
          ],
        ],
        [
          'title' => $this->t('Grow and learn', [], ['context' => 'Helsinki near you']),
          'service_links' => [
            [
              'link_label' => $this->t('Schools close to you', [], ['context' => 'Helsinki near you']),
              'link_url' => $this->getInternalSearchLink(
                InternalSearchLink::SCHOOLS,
                $addressName,
              ),
            ],
            [
              'link_label' => $this->t('Closest playgrounds and family houses', [], ['context' => 'Helsinki near you']),
              'link_url' => $this->getInternalSearchLink(
                InternalSearchLink::PLAYGROUNDS_FAMILY_HOUSES,
                $addressName,
              ),
            ],
            [
              'link_label' => $this->t('Closest daycare centres', [], ['context' => 'Helsinki near you']),
              'link_url' => $this->getInternalSearchLink(
                InternalSearchLink::DAYCARES,
                $addressName,
              ),
            ],
          ],
        ],
        [
          'title' => $this->t('Move around the city', [], ['context' => 'Helsinki near you']),
          'service_links' => [
            [
              'link_label' => $this->t('Roadway ploughing schedule', [], ['context' => 'Helsinki near you']),
              'link_url' => $this->getInternalSearchLink(
                InternalSearchLink::PLOWING_SCHEDULES,
                $addressName,
              ),
            ],
            [
              'link_label' => $this->t('Roadworks and events on map', [], ['context' => 'Helsinki near you']),
              'link_url' => $this->servicemap->getLink(ServiceMapLink::ROADWORK_EVENTS, $addressName),
            ],
            [
              'link_label' => $this->t('City bike stations on map', [], ['context' => 'Helsinki near you']),
              'link_url' => $this->servicemap->getLink(ServiceMapLink::CITYBIKE_STATIONS_STANDS, $addressName),
            ],
          ],
        ],
        [
          'title' => $this->t('The city is developing', [], ['context' => 'Helsinki near you']),
          'service_links' => [
            [
              'link_label' => $this->t('Street and park development on map', [], ['context' => 'Helsinki near you']),
              'link_url' => $this->servicemap->getLink(ServiceMapLink::STREET_PARK_PROJECTS, $addressName),
            ],
            [
              'link_label' => $this->t('Plans in process on map', [], ['context' => 'Helsinki near you']),
              'link_url' => $this->servicemap->getLink(ServiceMapLink::PLANS_IN_PROCESS, $addressName),
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Serves autocomplete suggestions for the search form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The result as JSON.
   */
  public function addressSuggestions(Request $request) : JsonResponse {
    $q = $request->query->get('q');
    $suggestions = [];

    $results = $this->servicemap->query($q, 10);

    foreach ($results as $result) {
      $name = $this->resolveTranslation($result->name);

      $suggestions[] = [
        'label' => $name,
        'value' => $name,
      ];
    }

    return new JsonResponse($suggestions);
  }

  /**
   * Get coordinates from servicemap API.
   *
   * @param string $address
   *   The address.
   *
   * @return array
   *   The coordinates.
   */
  protected function getCoordinates(string $address) : ?array {
    $results = $this->servicemap->query($address);

    if (
      isset($results['0']->name) &&
      isset($results['0']->location->coordinates)
    ) {
      return [
        'address_translations' => $results['0']->name,
        'coordinates' => $results['0']->location->coordinates,
      ];
    }

    return NULL;
  }

  /**
   * Resolves the translation string for given translation object.
   *
   * Returns the translation for the current language if it exists, otherwise
   * returns the Finnish translation.
   *
   * @param \stdClass $translations
   *   The translations object.
   *
   * @return string
   *   The translated string.
   */
  protected function resolveTranslation(\stdClass $translations) : string {
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    return $translations->{"$langcode"} ?? $translations->fi;
  }

  /**
   * Generate link to internal search with address param set.
   *
   * @param \Drupal\helfi_etusivu\Enum\InternalSearchLink $link
   *   Internal search link option.
   * @param string $address
   *   Address param for the link.
   *
   * @return string
   *   The resulting link.
   */
  protected function getInternalSearchLink(
    InternalSearchLink $link,
    string $address,
  ) : string {
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    $query = ['address' => urlencode($address)];
    $url = Url::fromUri(
      $link->getLinkTranslations()[$langcode],
      ['query' => $query],
    );

    return $url->toString();
  }

}
