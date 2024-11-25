<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Provides a 'HelsinkiNearYouHeroBlock' block.
 */
#[Block(
  id: "helsinki_near_you_hero_block",
  admin_label: new TranslatableMarkup("Helsinki near you hero block"),
)]
final class HelsinkiNearYouHeroBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() : array {
    $autosuggest_form = \Drupal::formBuilder()->getForm('Drupal\helfi_etusivu\Form\NearYouForm');

    $build['helsinki_near_you_hero_block'] = [
      '#autosuggest_form' => $autosuggest_form,
      '#theme' => 'helsinki_near_you_hero_block',
      '#result_page_url' => Url::fromRoute('helfi_etusivu.helsinki_near_you_results'),
      '#form_item_label' => $this->t('Address', [], ['context' => 'Helsinki near you']),
      '#form_item_placeholder' => $this->t('For example, Mannerheimintie 1', [], ['context' => 'Helsinki near you']),
      '#form_item_submit' => $this->t('Search', [], ['context' => 'Helsinki near you']),
      '#hero_title' => $this->t('Helsinki near you', [], ['context' => 'Helsinki near you']),
      '#hero_description' => $this->t('Find services, events and news close to you. Start by searching with your address.', [], ['context' => 'Helsinki near you']),
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
