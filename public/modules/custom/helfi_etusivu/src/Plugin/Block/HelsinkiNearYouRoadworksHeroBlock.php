<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides Helsinki Near You Roadworks Hero block.
 *
 * @Block(
 *   id = "helsinki_near_you_roadworks_hero_block",
 *   admin_label = @Translation("Helsinki Near You Roadworks Hero"),
 *   category = @Translation("helfi_etusivu")
 * )
 */
final class HelsinkiNearYouRoadworksHeroBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build['helsinki_near_you_roadworks_hero_block'] = [
      '#theme' => 'helsinki_near_you_roadworks_hero_block',
      '#hero_title' => $this->t('Street and park projects near you', [], ['context' => 'Helsinki near you']),
      '#hero_description' => $this->t('Find street and park projects in your neighbourhood.', [], ['context' => 'Helsinki near you roadworks search']),
    ];
    return $build;
  }

}
