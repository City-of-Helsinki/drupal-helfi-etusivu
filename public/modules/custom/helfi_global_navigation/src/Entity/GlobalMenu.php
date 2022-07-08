<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines global_menu entity class.
 *
 * @ContentEntityType(
 *   id = "global_menu",
 *   fieldable = FALSE,
 *   label = @Translation("HELfi Global menu"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\helfi_global_navigation\Entity\Listing\ListBuilder",
 *     "form" = {
 *       "default" = "Drupal\helfi_global_navigation\Form\GlobalMenuForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\helfi_global_navigation\Entity\Routing\GlobalMenuRouteProvider"
 *     }
 *   },
 *   base_table = "global_menu",
 *   data_table = "global_menu_field_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   },
 *   translatable = TRUE,
 *   admin_permission = "access content",
 *   links = {
 *     "canonical" = "/global_menu/{global_menu}",
 *     "edit-form" = "/admin/content/integrations/global_menu/{global_menu}/edit",
 *     "collection" = "/admin/content/integrations/global_menu",
 *     "delete-form" = "/admin/content/integrations/global_menu/{global_menu}/delete"
 *   }
 * )
 */
class GlobalMenu extends ContentEntityBase implements ContentEntityInterface {

  private const PROJECT_WEIGHTS = [
    'terveys' => 0,
    'kasvatus-koulutus' => 1,
    'asuminen' => 2,
    'liikenne' => 3,
    'kuva' => 4,
    'tyo-yrittaminen' => 5,
    'strategia' => 6,
    'rekry' => 7,
  ];

  const UNIVERSAL_HEADER_MENU = 'universal_header_menu';
  const MAIN_MENU = 'main_menu';
  const FOOTER_TOP_MENU = 'footer_top_menu';
  const FOOTER_TOP_MENU_2 = 'footer_top_menu_2';
  const FOOTER_BOTTOM_MENU = 'footer_bottom_menu';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['project'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Project'))
      ->setSetting('max_length', 50)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'label' => 'inline',
        'type' => 'readonly_field_widget',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['site_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Site name'))
      ->setSetting('max_length', 50)
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'label' => 'inline',
        'type' => 'readonly_field_widget',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['menu_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Menu type'))
      ->setSetting('max_length', 50)
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'label' => 'inline',
        'type' => 'readonly_field_widget',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['menu_tree'] = BaseFieldDefinition::create('json')
      ->setLabel(new TranslatableMarkup('Menu tree'))
      ->setDisplayOptions('form', [
        'type' => 'json_editor',
      ])
      ->setTranslatable(TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this menu in relation to other menus.'))
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the menu was created.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the menu was last edited.'));

    return $fields;
  }

  /**
   * Get project menu weight by project name.
   *
   * @param $project_name
   *
   * @return int|false
   */
  public static function getProjectWeight($project_name = NULL): int|FALSE {
    return array_key_exists($project_name, self::PROJECT_WEIGHTS)
      ? self::PROJECT_WEIGHTS[$project_name]
      : FALSE;
  }

}
