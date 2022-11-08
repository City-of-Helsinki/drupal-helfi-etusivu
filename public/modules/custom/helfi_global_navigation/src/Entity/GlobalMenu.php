<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
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
 *     "storage" = "Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "overview" = "Drupal\helfi_global_navigation\Entity\Form\GlobalMenuOverviewForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\helfi_global_navigation\Entity\Routing\GlobalMenuRouteProvider",
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
 *     "langcode" = "langcode",
 *     "published" = "status",
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
 *   field_ui_base_route = "entity.global_menu.collection"
 * )
 */
final class GlobalMenu extends ContentEntityBase implements ContentEntityInterface, EntityPublishedInterface {

  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Make sure entity id is set manually before saving.
    if (!$this->id()) {
      throw new \InvalidArgumentException('ID must be set before saving the entity.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += self::publishedBaseFieldDefinitions($entity_type);

    $fields[$entity_type->getKey('id')] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setSettings([
        'is_ascii' => TRUE,
      ])
      ->setDisplayOptions('form', [
        'label' => 'inline',
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Site name'))
      ->setSetting('max_length', 50)
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'label' => 'inline',
        'type' => 'readonly_field_widget',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['menu_tree'] = BaseFieldDefinition::create('json')
      ->setLabel(new TranslatableMarkup('Menu tree'))
      ->addConstraint('JsonSchema', [
        'schema' => 'file://' . realpath(__DIR__ . '/../../assets/schema.json'),
      ])
      ->setTranslatable(TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this menu in relation to other menus.'))
      ->setDefaultValue(0);

    return $fields;
  }

  /**
   * Gets the weight.
   *
   * @return int
   *   The weight.
   */
  public function getWeight() : int {
    return (int) $this->get('weight')->value ?? 0;
  }

  /**
   * Sets the weight.
   *
   * @param int $weight
   *   The weight.
   *
   * @return $this
   *   The self.
   */
  public function setWeight(int $weight) : self {
    $this->set('weight', $weight);
    return $this;
  }

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
    if (is_object($tree)) {
      $tree = \json_encode($tree, flags: JSON_THROW_ON_ERROR);
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
   * Get menu tree.
   *
   * @param bool $associative
   *   Return as associative array instead of object.
   *
   * @return object|null
   *   Menu tree.
   */
  public function getMenuTree(bool $associative = FALSE): ? object {
    if ($menu_tree = $this->get('menu_tree')->value) {
      return \json_decode($menu_tree, $associative, flags: JSON_THROW_ON_ERROR);
    }
    return NULL;
  }

}
