<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Enum;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Configurable images of the Helsinki near you landing page.
 *
 * Each image is bound to the paragraph whose image field it mimics, so that
 * the image can be selected and validated like in that paragraph.
 */
enum LandingPageImage: string {

  case Hero = 'hero';
  case Liftup = 'liftup';

  /**
   * Gets the paragraph type the image belongs to.
   *
   * @return string
   *   The paragraph type.
   */
  public function getParagraphType(): string {
    return match ($this) {
      self::Hero => 'hero',
      self::Liftup => 'liftup_with_image',
    };
  }

  /**
   * Gets the media reference field of the paragraph type.
   *
   * @return string
   *   The field name.
   */
  public function getFieldName(): string {
    return match ($this) {
      self::Hero => 'field_hero_image',
      self::Liftup => 'field_liftup_with_image_image',
    };
  }

  /**
   * Gets the label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label.
   */
  public function getLabel(): TranslatableMarkup {
    return match ($this) {
      self::Hero => new TranslatableMarkup('Hero image', [], ['context' => 'Helsinki near you']),
      self::Liftup => new TranslatableMarkup('Liftup image', [], ['context' => 'Helsinki near you']),
    };
  }

}
