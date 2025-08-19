<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

final class HelsinkiNearYouTextProvider {
  use StringTranslationTrait;

  /**
   * Get the hero title based on the route.
   */
  public function getTitle(RouteMatchInterface $route_match) : TranslatableMarkup {
    return match($route_match->getRouteName()) {
      'helfi_etusivu.helsinki_near_you' => $this->t('Helsinki near you', [], ['context' => 'Helsinki near you']),
      'helfi_etusivu.helsinki_near_you_feedbacks' => $this->t('Feedback near you', [], ['context' => 'Helsinki near you']),
      'helfi_etusivu.helsinki_near_you_events' => $this->t('Events near you', [], ['context' => 'Helsinki near you']),
      'helfi_etusivu.helsinki_near_you_roadworks' => $this->t('Street and park projects near you', [], ['context' => 'Helsinki near you']),
      default => '',
    };
  }

  /**
   * Get the hero description based on the route.
   */
  public function getDescription(RouteMatchInterface $route_match) : TranslatableMarkup {
    return match($route_match->getRouteName()) {
      'helfi_etusivu.helsinki_near_you' => $this->t('Discover city services, events and news near you. Start by entering your street address.', [], ['context' => 'Helsinki near you']),
      'helfi_etusivu.helsinki_near_you_results' => $this->t('Discover city services, events and news near you.', [], ['context' => 'Helsinki near you']),
      'helfi_etusivu.helsinki_near_you_feedbacks' => $this->t('Find feedback in your neighbourhood.', [], ['context' => 'Helsinki near you']),
      'helfi_etusivu.helsinki_near_you_events' => $this->t('Find events in your neighbourhood that interest you.', [], ['context' => 'Helsinki near you events search']),
      'helfi_etusivu.helsinki_near_you_roadworks' => $this->t('Find street and park projects in your neighbourhood.', [], ['context' => 'Helsinki near you roadworks search']),
      default => '',
    };
  }
}
