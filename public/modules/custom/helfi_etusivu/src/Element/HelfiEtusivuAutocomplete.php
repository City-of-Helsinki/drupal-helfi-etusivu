<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Element;

use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element\Textfield;

/**
 * Autocomplete element for Helsinki near you form.
 *
 * @todo evaluate if this could be used domain-wide.
 */
#[FormElement('helfi_etusivu_autocomplete')]
class HelfiEtusivuAutocomplete extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $info = parent::getInfo();
    $class = static::class;
    $info['#process'][] = [$class, 'processHelfiEtusivuAutocomplete'];
    return $info;
  }

  /**
   * Preprocess callback.
   */
  public static function processHelfiEtusivuAutocomplete(array $element): array {
    $element['#attached']['library'][] = 'hdbt_subtheme/helfi_etusivu_autocomplete';
    $element['#attributes']['data-helfi-etusivu-autocomplete'] = TRUE;

    // Remove "form-autocomplete" class.
    // This prevents Drupal autocomplete from hijacking the element.
    $element['#attributes']['class'] = array_filter(
      $element['#attributes']['class'] ?? [],
      fn ($class) => $class !== 'form-autocomplete'
    );

    return $element;
  }

}
