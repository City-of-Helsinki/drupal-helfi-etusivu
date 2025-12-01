<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\HeroOptions;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\LandingPageSearchForm;
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
   * The form builder.
   */
  private FormBuilderInterface $formBuilder;

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
    $instance->formBuilder = $container->get(FormBuilderInterface::class);
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
    $routeInformation = RouteInformationEnum::fromRoute($route);
    if (!$routeInformation) {
      return [];
    }

    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Runtime options for the route.
    $routeOptions = fn (RouteInformationEnum|NULL $routeInformation) => match ($routeInformation) {
      RouteInformationEnum::ROADWORKS, RouteInformationEnum::EVENTS, RouteInformationEnum::FEEDBACK => new HeroOptions(),
      RouteInformationEnum::RESULTS => new HeroOptions(
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
          ->setCacheMaxAge(0),
      ),
      RouteInformationEnum::LANDING_PAGE => new HeroOptions(form: $this->formBuilder->getForm(LandingPageSearchForm::class)),
    };

    return $this->buildHero($routeInformation, $routeOptions($routeInformation));
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
