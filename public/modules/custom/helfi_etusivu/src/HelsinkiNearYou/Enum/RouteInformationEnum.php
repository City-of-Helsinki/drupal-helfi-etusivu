<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Enum;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides translated titles and descriptions for specific routes.
 *
 * Uses StringTranslationTrait to return translatable markup.
 */
enum RouteInformationEnum {

  case LANDING_PAGE;
  case RESULTS;
  case FEEDBACK;
  case EVENTS;
  case ROADWORKS;

  /**
   * Returns the hero title based on the route.
   *
   * @param array $arguments
   *   Arguments for translation.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated title.
   */
  public function getTitle(array $arguments) : TranslatableMarkup {
    return match($this) {
      self::LANDING_PAGE => new TranslatableMarkup('Helsinki near you', [], ['context' => 'Helsinki near you']),
      self::RESULTS => new TranslatableMarkup('Helsinki near you: @address', $arguments, ['context' => 'Helsinki near you']),
      self::FEEDBACK  => new TranslatableMarkup('Feedback related to your neighbourhood', [], ['context' => 'Helsinki near you']),
      self::EVENTS => new TranslatableMarkup('Events near you', [], ['context' => 'Helsinki near you']),
      self::ROADWORKS => new TranslatableMarkup('Street and park projects near you', [], ['context' => 'Helsinki near you']),
    };
  }

  /**
   * Returns the hero description based on the route.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated description.
   */
  public function getDescription() : TranslatableMarkup {
    return match($this) {
      self::LANDING_PAGE => new TranslatableMarkup('Discover city services, events and news near you. Start by entering your street address.', [], ['context' => 'Helsinki near you']),
      self::RESULTS => new TranslatableMarkup('Discover city services, events and news near you.', [], ['context' => 'Helsinki near you']),
      self::FEEDBACK => new TranslatableMarkup('Browse feedback and fault reports that have been sent to the City of Helsinki from near you.', [], ['context' => 'Helsinki near you']),
      self::EVENTS => new TranslatableMarkup('Find interesting events near you.', [], ['context' => 'Helsinki near you events search']),
      self::ROADWORKS => new TranslatableMarkup('Find information on street and park projects near you.', [], ['context' => 'Helsinki near you roadworks search']),
    };
  }

  /**
   * Returns the hero description render array.
   */
  public function getHeroDescription(): TranslatableMarkup|array {
    return match($this) {
      self::RESULTS => array_merge(
        Link::createFromRoute(new TranslatableMarkup('Edit address', [], ['context' => 'Helsinki near you']), 'helfi_etusivu.helsinki_near_you')->toRenderable(),
        [
          '#attributes' => [
            'class' => ['hds-button', 'hds-button--supplementary'],
          ],
        ],
      ),
      default => $this->getDescription(),
    };
  }

  /**
   * Returns the case that corresponds to a given route name.
   *
   * If the provided route does not have a matching enum
   * case, `null` is returned.
   *
   * @param string $route
   *   The route name.
   *
   * @return static|null
   *   The corresponding enum case if a match exists, or
   *   NULL if no match was found.
   */
  public static function fromRoute(string $route): ?self {
    return match($route) {
      'helfi_etusivu.helsinki_near_you' => self::LANDING_PAGE,
      'helfi_etusivu.helsinki_near_you_results' => self::RESULTS,
      'helfi_etusivu.helsinki_near_you_feedbacks' => self::FEEDBACK,
      'helfi_etusivu.helsinki_near_you_events' => self::EVENTS,
      'helfi_etusivu.helsinki_near_you_roadworks' => self::ROADWORKS,
      default => NULL,
    };
  }

  /**
   * Get the boolean for first paragraphs background status.
   *
   * The hero needs to have gray background color if the
   * first paragraph right after hero also has gray background.
   *
   * @return bool
   *   TRUE if the first paragraph right after hero also
   *   has gray background, FALSE otherwise.
   */
  public function getFirstParagraphBg(): bool {
    return match($this) {
      self::ROADWORKS, self::EVENTS, self::FEEDBACK => TRUE,
      self::RESULTS, self::LANDING_PAGE => FALSE,
    };
  }

}
