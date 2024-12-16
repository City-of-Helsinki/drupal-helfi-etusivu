<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Element;

use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element\Textfield;

/**
 * Autocomplete element for Helsinki near you form.
 *
 * To be evaluated if this could be used domain-wide.
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
    $element['#attached']['library'] = 'hdbt_subtheme/helfi_etusivu_autocomplete';
    $element['#attributes']['data-helfi-etusivu-autocomplete'] = TRUE;
    return $element;
  }

}
