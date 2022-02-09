<?php

declare(strict_types=1);

namespace Drupal\helfi_linkedevents\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_api_base\Entity\RemoteEntityBase;

/**
 * Defines the linkedevents_event entity class.
 *
 * @ContentEntityType(
 *   id = "linkedevents_event",
 *   label = @Translation("Linked Events - Event"),
 *   label_collection = @Translation("Linked Events - Event"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\helfi_api_base\Entity\Access\RemoteEntityAccess",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\helfi_api_base\Entity\Routing\EntityRouteProvider",
 *     }
 *   },
 *   base_table = "linkedevents_event",
 *   data_table = "linkedevents_event_field_data",
 *   revision_table = "linkedevents_event_revision",
 *   revision_data_table = "linkedevents_event_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer remote entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "uid" = "uid",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   revision_metadata_keys = {
 *     "revision_created" = "revision_timestamp",
 *     "revision_user" = "revision_user",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/linkedevents-event/{linkedevents_event}",
 *     "edit-form" = "/admin/content/linkedevents-event/{linkedevents_event}/edit",
 *     "delete-form" = "/admin/content/linkedevents-event/{linkedevents_event}/delete",
 *     "collection" = "/admin/content/linkedevents-event"
 *   },
 *   field_ui_base_route = "linkedevents_event.settings"
 * )
 */
final class Event extends RemoteEntityBase {

  use RevisionLogEntityTrait;

  /**
   * Adds the given offer.
   *
   * @param \Drupal\helfi_linkedevents\Entity\Offer $offer
   *   The offer.
   *
   * @return $this
   *   The self.
   */
  public function addOffer(Offer $offer) : self {
    if (!$this->hasOffer($offer)) {
      $this->get('offers')->appendItem($offer);
    }
    return $this;
  }

  /**
   * Removes the given offer.
   *
   * @param \Drupal\helfi_linkedevents\Entity\Offer $offer
   *   The offer.
   *
   * @return $this
   *   The self.
   */
  public function removeOffer(Offer $offer) : self {
    $index = $this->getOfferIndex($offer);
    if ($index !== FALSE) {
      $this->get('offers')->offsetUnset($index);
    }
    return $this;
  }

  /**
   * Checks whether the offer exists or not.
   *
   * @param \Drupal\helfi_linkedevents\Entity\Offer $offer
   *   The offer.
   *
   * @return bool
   *   Whether we have given offer or not.
   */
  public function hasOffer(Offer $offer) : bool {
    return $this->getOfferIndex($offer) !== FALSE;
  }

  /**
   * Gets the index of the given offer.
   *
   * @param \Drupal\helfi_linkedevents\Entity\Offer $offer
   *   The offer.
   *
   * @return int|bool
   *   The index of the given offer, or FALSE if not found.
   */
  protected function getOfferIndex(Offer $offer) {
    $values = $this->get('offers')->getValue();
    $ids = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    return array_search($offer->id(), $ids);
  }

  /**
   * Gets the data.
   *
   * @param string $key
   *   The key.
   * @param null|mixed $default
   *   The default value.
   *
   * @return mixed|null
   *   The data.
   */
  public function getData(string $key, $default = NULL) {
    $data = [];
    if (!$this->get('data')->isEmpty()) {
      $data = $this->get('data')->first()->getValue();
    }
    return isset($data[$key]) ? $data[$key] : $default;
  }

  /**
   * Sets the data.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   *
   * @return $this
   *   The self.
   */
  public function setData(string $key, $value) : self {
    $this->get('data')->__set($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setCardinality(1)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ]);

    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Location'))
      ->setDefaultValue('')
      ->setCardinality(1)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['provider'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Provider'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setCardinality(1)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['short_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Short description'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'rows' => 6,
      ])
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setCardinality(1)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Description'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'rows' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Info url.
    $fields['info_url'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Info URL'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setCardinality(1)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Start time: datetime.
    $fields['start_time'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start time'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // End time: datetime.
    $fields['end_time'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End time'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Offers.
    $fields['offers'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Offers'))
      ->setSettings([
        'target_type' => 'linkedevents_offer',
        'handler_settings' => [
          'target_bundles' => ['linkedevents_offer'],
        ],
      ])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // sub_events 1-n string.
    $fields['sub_events'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Sub events'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Keywords 1-n.
    $fields['keywords'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Keywords'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Images (links only)
    $fields['images'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Images'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Videos (links only)
    $fields['videos'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Videos'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Data'))
      ->setDescription(new TranslatableMarkup('A serialized array of additional data.'));

    return $fields;
  }

}
