<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\DebugDataItem;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\helfi_api_base\DebugDataItemPluginBase;
use Drupal\helfi_global_navigation\Entity\GlobalMenu;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the debug_data_item.
 *
 * @DebugDataItem(
 *   id = "helfi_globalmenu",
 *   label = @Translation("Global menu entity status"),
 *   description = @Translation("Global menu entity status")
 * )
 */
final class GlobalMenuEntityStatus extends DebugDataItemPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private DateFormatterInterface $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new self($configuration, $plugin_id, $plugin_definition);
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function collect(): array {
    $data = [];

    /** @var \Drupal\helfi_global_navigation\Entity\GlobalMenu $menu */
    foreach (GlobalMenu::loadMultiple() as $menu) {
      $project = $menu->get('project')->value;

      foreach (['fi', 'en', 'sv'] as $langcode) {
        $status = $updated = 0;

        try {
          $translation = $menu->getTranslation($langcode);
          $status = $translation->isPublished();
          $updated = $translation->get('content_translation_changed')->value;
        }
        catch (\Exception) {
        }
        $data[$project]["{$langcode}_status"] = $status;
        $data[$project]["{$langcode}_updated"] = $this->dateFormatter->format($updated, 'long');
      }
    }

    return $data;
  }

}
