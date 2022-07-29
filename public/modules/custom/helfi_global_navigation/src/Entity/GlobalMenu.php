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
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\helfi_api_base\Entity\Routing\EntityRouteProvider",
 *     },
 *     "local_action_provider" = {
 *       "collection" = "Drupal\entity\Menu\EntityCollectionLocalActionProvider",
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *   },
 *   base_table = "global_menu",
 *   data_table = "global_menu_field_data",
 *   entity_keys = {
 *     "id" = "project",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   },
 *   translatable = TRUE,
 *   admin_permission = "administer global_menu",
 *   links = {
 *     "canonical" = "/global_menu/{global_menu}",
 *     "add-form" = "/admin/content/integrations/global_menu/add",
 *     "edit-form" = "/admin/content/integrations/global_menu/{global_menu}/edit",
 *     "collection" = "/admin/content/integrations/global_menu",
 *     "delete-form" = "/admin/content/integrations/global_menu/{global_menu}/delete"
 *   },
 *   field_ui_base_route = "global_menu.settings"
 * )
 */
final class GlobalMenu extends ContentEntityBase implements ContentEntityInterface {

  /**
   * Setter for menu_tree field.
   *
   * @param mixed $tree
   *   The menu tree.
   *
   * @return $this
   *   The self.
   */
  public function setMenuTree(mixed $tree) : self {
    if (!is_string($tree)) {
      $tree = json_encode($tree);
    }
    $this->set('menu_tree', $tree);
    return $this;
  }

  /**
   * Setter for label field.
   *
   * @param string $label
   *   The label.
   *
   * @return $this
   *   The self.
   */
  public function setLabel(string $label) : self {
    $this->set($this->getEntityType()->getKey('label'), $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields[$entity_type->getKey('id')] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setSettings([
        'is_ascii' => TRUE,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Name'))
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
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'json_editor',
      ])
      ->addConstraint('JsonSchema', [
        'schema' => 'file://' . realpath(__DIR__ . '/../../assets/schema.json'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
