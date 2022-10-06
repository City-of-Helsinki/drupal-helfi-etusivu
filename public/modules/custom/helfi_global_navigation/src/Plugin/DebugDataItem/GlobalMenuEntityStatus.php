<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Plugin\DebugDataItem;

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
class GlobalMenuEntityStatus extends DebugDataItemPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function collect(): array {
    $data = [];

    $menus = GlobalMenu::loadMultiple();
    foreach ($menus as $menu) {
      $menu_name = $menu->get('project')->value;

      foreach (['fi', 'en', 'sv'] as $langcode) {
        try {
          $translation = $menu->getTranslation($langcode);
          $data[$menu_name]["{$langcode}_status"] = $translation->get('status')->value ? 1 : 0;
          $data[$menu_name]["{$langcode}_updated"] = $translation->content_translation_changed->value ? date('d.m.Y H:i', (int) $translation->content_translation_changed->value) : 0;
        }
        catch (\Exception $e) {
          $data[$menu_name]["{$langcode}_status"] = 0;
          $data[$menu_name]["{$langcode}_updated"] = 0;
        }
      }
    }

    return $data;
  }

}
