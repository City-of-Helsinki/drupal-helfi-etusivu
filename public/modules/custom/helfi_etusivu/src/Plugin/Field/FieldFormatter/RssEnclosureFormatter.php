<?php

namespace Drupal\helfi_etusivu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\MediaInterface;

/**
 * Plugin implementation to get image into enclosure in rss feed.
 *
 * @FieldFormatter(
 *   id = "rss_enclosure_formatter",
 *   label = @Translation("Rss enclosure formatter"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class RssEnclosureFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      if (
        $item->entity instanceof MediaInterface &&
        $item->entity->hasField('field_media_image')
      ) {
        $image_style = ImageStyle::load('og_image');
        $image_entity = $item->entity->field_media_image;

        if ($image_entity && $image_entity->isEmpty()) {
          break;
        }

        $image_path = $image_entity->entity->getFileUri();

        $elements[$delta] = [
          '#markup' => $image_style->buildUrl($image_path),
        ];
      }
    }

    return $elements;
  }

}
