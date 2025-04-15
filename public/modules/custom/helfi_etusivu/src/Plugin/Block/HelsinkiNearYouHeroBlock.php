<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
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
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
    );
  }

  /**
   * Constructs a new HelsinkiNearYouHeroBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    private readonly FormBuilderInterface $formBuilder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function build() : array {
    $autosuggest_form = $this->formBuilder->getForm('Drupal\helfi_etusivu\Form\NearYouForm');

    $build['helsinki_near_you_hero_block'] = [
      '#autosuggest_form' => $autosuggest_form,
      '#theme' => 'helsinki_near_you_hero_block',
      '#result_page_url' => Url::fromRoute('helfi_etusivu.helsinki_near_you_results'),
      '#form_item_label' => $this->t('Address', [], ['context' => 'Helsinki near you']),
      '#form_item_placeholder' => $this->t('For example, Mannerheimintie 1', [], ['context' => 'Helsinki near you']),
      '#form_item_submit' => $this->t('Search', [], ['context' => 'Helsinki near you']),
      '#hero_title' => $this->t('Helsinki near you', [], ['context' => 'Helsinki near you']),
      '#hero_description' => $this->t('Discover city services, events and news near you. Start by entering your street address.', [], ['context' => 'Helsinki near you']),
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
