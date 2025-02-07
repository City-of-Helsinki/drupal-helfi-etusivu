<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'HelsinkiNearYouEventsHeroBlock' block.
 */
#[Block(
  id: "helsinki_near_you_events_hero_block",
  admin_label: new TranslatableMarkup("Helsinki near you events hero block"),
)]
final class HelsinkiNearYouEventsHeroBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() : array {
    $build['helsinki_near_you_hero_block'] = [
      '#theme' => 'helsinki_near_you_events_hero_block',
      '#hero_title' => $this->t('Events near you', [], ['context' => 'Helsinki near you']),
      '#hero_description' => $this->t('Find events in your neighbourhood that interest you.', [], ['context' => 'Helsinki near you events search']),
    ];
    return $build;
  }

}
