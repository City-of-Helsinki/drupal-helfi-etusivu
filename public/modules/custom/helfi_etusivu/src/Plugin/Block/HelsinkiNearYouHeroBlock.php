<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\HeroOptions;
use Drupal\helfi_etusivu\HelsinkiNearYou\Enum\RouteInformationEnum;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'HelsinkiNearYouHeroBlock' block.
 */
#[Block(
  id: "helsinki_near_you_hero_block",
  admin_label: new TranslatableMarkup("Helsinki near you hero block"),
)]
final class HelsinkiNearYouHeroBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   */
  private RouteMatchInterface $routeMatch;

  /**
   * The request stack.
   */
  private RequestStack $requestStack;

  /**
   * The language manager.
   */
  private LanguageManagerInterface $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    $configuration,
    $plugin_id,
    $plugin_definition,
  ) : self {
    $instance = new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
    $instance->routeMatch = $container->get(RouteMatchInterface::class);
    $instance->requestStack = $container->get(RequestStack::class);
    $instance->languageManager = $container->get(LanguageManagerInterface::class);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() : array {
    $route = $this->routeMatch->getRouteName();
    if (!$route) {
      return [];
    }
    $routeInformation = RouteInformationEnum::fromRoute($route);
    // The landing page renders its own hero and search form.
    if (!$routeInformation || $routeInformation === RouteInformationEnum::LandingPage) {
      return [];
    }

    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Runtime options for the route.
    $options = match ($routeInformation) {
      RouteInformationEnum::Roadworks, RouteInformationEnum::Events, RouteInformationEnum::Feedback => new HeroOptions(),
      RouteInformationEnum::Results => new HeroOptions(
        translationArguments: [
          // Get the address name from request attributes.
          '@address' => $this
            ->requestStack
            ->getCurrentRequest()
          ?->attributes
            ->get('helsinki_near_you_address')
          ?->streetName
            ->getName($langcode) ?? '',
        ],
        // Response depends on the route arguments.
        cache: (new CacheableMetadata())
          ->setCacheContexts(['url.query_args']),
      ),
    };

    return $this->buildHero($routeInformation, $options);
  }

  /**
   * Builds a hero block.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Enum\RouteInformationEnum $routeInformation
   *   The route information.
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\DTO\HeroOptions $options
   *   The route option.
   *
   * @return array
   *   The render array.
   */
  private function buildHero(RouteInformationEnum $routeInformation, HeroOptions $options) : array {
    $build['helsinki_near_you_hero_block'] = [
      '#theme' => 'helsinki_near_you_hero_block',
      '#hero_title' => $routeInformation->getTitle($options->translationArguments),
      '#hero_description' => $routeInformation->getHeroDescription(),
      '#first_paragraph_bg' => $routeInformation->getFirstParagraphBg(),
      '#form' => $options->form,
    ];

    $options->cache?->applyTo($build);

    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheContexts() : array {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
