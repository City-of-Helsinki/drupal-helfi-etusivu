<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Normalizer;

use Drupal\helfi_global_navigation\Entity\GlobalMenu;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;

/**
 * Converts the Drupal entity object structures to a normalized array.
 */
final class MenuTreeNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = GlobalMenu::class;

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) : bool {
    if ($data instanceof GlobalMenu && $this->checkFormat($format)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) : array {
    $attributes = parent::normalize($entity, $format, $context);

    if (isset($attributes['menu_tree'])) {
      $attributes['menu_tree'] = array_map(
        fn (array $value) => \json_decode($value['value'], flags: JSON_THROW_ON_ERROR),
        $attributes['menu_tree']
      );
    }

    return $attributes;
  }

}
