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

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() : array {
    return match($this->routeMatch->getRouteName()) {
      'helfi_etusivu.helsinki_near_you_roadworks' => $this->buildHero(
        $this->t('Street and park projects near you', [], ['context' => 'Helsinki near you']),
        $this->t('Find street and park projects in your neighbourhood.', [], ['context' => 'Helsinki near you roadworks search']),
        true,
      ),
      'helfi_etusivu.helsinki_near_you_events' => $this->buildHero(
        $this->t('Events near you', [], ['context' => 'Helsinki near you']),
        $this->t('Find events in your neighbourhood that interest you.', [], ['context' => 'Helsinki near you events search']),
        true,
      ),
      'helfi_etusivu.helsinki_near_you_feedbacks' => $this->buildHero(
        $this->t('Feedbacks near you', [], ['context' => 'Helsinki near you']),
        $this->t('Find feedbacks in your neighbourhood.', [], ['context' => 'Helsinki near you feedbacks search']),
        true,
      ),
      'helfi_etusivu.helsinki_near_you' => $this->buildHero(
        $this->t('Helsinki near you', [], ['context' => 'Helsinki near you']),
        $this->t('Discover city services, events and news near you. Start by entering your street address.', [], ['context' => 'Helsinki near you']),
        false,
        $this->formBuilder->getForm(LandingPageSearchForm::class),
      ),
      default => [],
    };
  }

  /**
   * Builds a hero block.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The hero title.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The hero description.
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
