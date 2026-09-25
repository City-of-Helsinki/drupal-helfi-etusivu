<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\taxonomy\TermInterface;

/**
 * Contact details of a City employee from from numerot.hel.fi.
 *
 * @see \Drupal\helfi_etusivu\Plugin\migrate\source\NumerotContactDetails
 */
#[ContentEntityType(
  id: 'helfi_search_contact',
  label: new TranslatableMarkup('Contact details', options: ['context' => 'Helfi search']),
  label_collection: new TranslatableMarkup('Contact details', options: ['context' => 'Helfi search']),
  label_singular: new TranslatableMarkup('contact details', options: ['context' => 'Helfi search']),
  label_plural: new TranslatableMarkup('contact details', options: ['context' => 'Helfi search']),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'email',
    'langcode' => 'langcode',
  ],
  handlers: [
    'access' => EntityAccessControlHandler::class,
  ],
  admin_permission: 'administer search content',
  base_table: 'helfi_search_contact',
  data_table: 'helfi_search_contact_data',
  translatable: TRUE,
)]
final class ContactDetails extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // The numerot.hel.fi API ID is used as the entity ID.
    $fields[(string) $entity_type->getKey('id')] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setSetting('is_ascii', TRUE)
      ->setReadOnly(TRUE);

    $fields['name_parts'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Name parts', options: ['context' => 'Helfi search']))
      ->setDescription(new TranslatableMarkup('The name split into parts.', options: ['context' => 'Helfi search']))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('max_length', 255);

    $fields[(string) $entity_type->getKey('label')] = BaseFieldDefinition::create('email')
      ->setLabel(new TranslatableMarkup('Email', options: ['context' => 'Helfi search']));

    $fields['phones'] = BaseFieldDefinition::create('helfi_search_contact_value')
      ->setLabel(new TranslatableMarkup('Phone numbers', options: ['context' => 'Helfi search']))
      ->setDescription(new TranslatableMarkup('Type is one of mobile, landline or customer_service.', options: ['context' => 'Helfi search']))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['addresses'] = BaseFieldDefinition::create('helfi_search_contact_value')
      ->setLabel(new TranslatableMarkup('Addresses', options: ['context' => 'Helfi search']))
      ->setDescription(new TranslatableMarkup('Type is either street or mail. Lines are separated by a newline.', options: ['context' => 'Helfi search']))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['job_title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Job title', options: ['context' => 'Helfi search']))
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255);

    $fields['service_hours'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Phone service hours', options: ['context' => 'Helfi search']))
      ->setTranslatable(TRUE);

    $fields['organization'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Organization', options: ['context' => 'Helfi search']))
      ->setDescription(new TranslatableMarkup('The organization.', options: ['context' => 'Helfi search']))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['numerot_organization' => 'numerot_organization']]);

    return $fields;
  }

  /**
   * Gets the organization.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The organization term.
   */
  public function getOrganization(): ?TermInterface {
    $organization = $this->get('organization')->entity;
    return $organization instanceof TermInterface ? $organization : NULL;
  }

}
