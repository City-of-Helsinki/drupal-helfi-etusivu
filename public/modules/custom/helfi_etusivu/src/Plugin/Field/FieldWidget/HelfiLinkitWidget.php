<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\linkit\Plugin\Field\FieldWidget\LinkitWidget;
use Drupal\linkit\Utility\LinkitHelper;

/**
 * Overrides the linkit widget.
 */
#[FieldWidget(
  id: "helfi_linkit",
  label: new TranslatableMarkup('Helfi: Linkit'),
  field_types: ['link']
)]

class HelfiLinkitWidget extends LinkitWidget {

  /**
   * Circumvent Linkit to allow linking to internal pages directly.
   *
   * This prevents stripping language param from internal links.
   */
  protected function convertToUri(string $input) {
    if (
      UrlHelper::isExternal($input) &&
      UrlHelper::externalIsLocal($input, \Drupal::request()->getSchemeAndHttpHost())
    ) {
      return $input;
    }

    return LinkitHelper::uriFromUserInput($input);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value['uri'] = $this->convertToUri($value['uri']);
      $value += ['options' => $value['attributes']];
    }
    return $values;
  }

}
