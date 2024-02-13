<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) : array {
    $storage = $this->entityTypeManager->getStorage('image_style');

    $elements = [];
    foreach ($items as $delta => $item) {
      if (
        $item->entity instanceof MediaInterface &&
        $item->entity->hasField('field_media_image')
      ) {
        $image_style = $storage->load('og_image');
        // @phpstan-ignore-next-line
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
