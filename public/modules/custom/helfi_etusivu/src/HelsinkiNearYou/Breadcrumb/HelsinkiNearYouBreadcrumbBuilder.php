<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\HelsinkiNearYou\Enum\RouteInformationEnum;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Breadcrumb builder for Helsinki Near You pages.
 */
final class HelsinkiNearYouBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function applies(RouteMatchInterface $route_match, ?CacheableMetadata $cacheable_metadata = NULL): bool {
    $cacheable_metadata?->addCacheContexts(['route']);
    $route = $route_match->getRouteName();
    return $route && RouteInformationEnum::fromRoute($route) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function build(RouteMatchInterface $route_match): Breadcrumb {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route', 'url.query_args:home_address']);

    // Frontpage is injected as first link by
    // helfi_platform_config_system_breadcrumb_alter.
    $route = $route_match->getRouteName();
    assert($route);
    $routeEnum = RouteInformationEnum::fromRoute($route);

    if ($routeEnum === RouteInformationEnum::LandingPage) {
      $breadcrumb->addLink(Link::createFromRoute(
        RouteInformationEnum::LandingPage->getTitle([]),
        '<none>',
      ));

      return $breadcrumb;
    }

    $breadcrumb->addLink(Link::createFromRoute(
      RouteInformationEnum::LandingPage->getTitle([]),
      'helfi_etusivu.helsinki_near_you',
    ));

    $address = $this->requestStack->getCurrentRequest()?->query->get('home_address');

    if ($address) {
      $text = new TranslatableMarkup("Results for @address", ['@address' => urldecode($address)], [
        'context' => 'Helsinki near you',
      ]);

      if ($routeEnum === RouteInformationEnum::Results) {
        // On results page, show address as current page.
        $breadcrumb->addLink(Link::createFromRoute($text, '<none>'));
      }
      else {
        // On sub-pages, link back to results with address.
        $breadcrumb->addLink(Link::fromTextAndUrl(
          $text,
          // Javascript apps want to alter this link. Add id for targeting.
          Url::fromRoute('helfi_etusivu.helsinki_near_you_results', options: [
            'query' => ['home_address' => $address],
            'attributes' => ['id' => 'hny-address-breadcrumb'],
          ]),
        ));
        $breadcrumb->addLink(Link::createFromRoute($routeEnum->getTitle([]), '<none>'));
      }
    }

    return $breadcrumb;
  }

}
