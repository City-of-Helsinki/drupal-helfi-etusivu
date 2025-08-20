<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\LandingPageSearchForm;
use Drupal\helfi_etusivu\HelsinkiNearYou\HelsinkiNearYouTextProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  private FormBuilderInterface $formBuilder;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private RouteMatchInterface $routeMatch;

  /**
   * The text provider.
   *
   * @var \Drupal\helfi_etusivu\HelsinkiNearYou\HelsinkiNearYouTextProvider
   */
  private HelsinkiNearYouTextProvider $textProvider;

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
    $instance->textProvider = $container->get(HelsinkiNearYouTextProvider::class);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() : array {
    $route = $this->routeMatch->getRouteName();

    // Routes with their options.
    $routeOptions = [
      'helfi_etusivu.helsinki_near_you_roadworks' => ['gray' => TRUE],
      'helfi_etusivu.helsinki_near_you_events' => ['gray' => TRUE],
      'helfi_etusivu.helsinki_near_you_feedbacks' => ['gray' => TRUE],
      'helfi_etusivu.helsinki_near_you' => [
        'gray' => FALSE,
        'form' => $this->formBuilder->getForm(LandingPageSearchForm::class),
      ],
    ];

    if (!isset($routeOptions[$route])) {
      return [];
    }

    return $this->buildHero(
      $this->textProvider->getTitle($this->routeMatch),
      $this->textProvider->getDescription($this->routeMatch),
      $routeOptions[$route]['gray'],
      $routeOptions[$route]['form'] ?? [],
    );
  }

  /**
   * Builds a hero block.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The hero title.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The hero description.
   * @param bool $first_paragrap_gray
   *   Tells template if the first paragraph has gray bg.
   * @param array $form
   *   The hero form.
   *
   * @return array
   *   The render array.
   */
  private function buildHero(TranslatableMarkup $title, TranslatableMarkup $description, bool $first_paragrap_gray, array $form = []) : array {
    $build['helsinki_near_you_hero_block'] = [
      '#theme' => 'helsinki_near_you_hero_block',
      '#hero_title' => $title,
      '#hero_description' => $description,
      '#first_paragraph_grey' => $first_paragrap_gray,
      '#form' => $form,
    ];
    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheContexts() : array {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
