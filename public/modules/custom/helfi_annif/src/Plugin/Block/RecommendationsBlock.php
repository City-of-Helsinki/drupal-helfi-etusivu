<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides 'AI powered recommendations'.
 */
#[Block(
  id: "helfi_recommendations",
  admin_label: new TranslatableMarkup("AI powered recommendations"),
)]
class RecommendationsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // @todo UHF-9962.
    return [
      '#markup' => $this->t('Hello, World!'),
    ];
  }

}
