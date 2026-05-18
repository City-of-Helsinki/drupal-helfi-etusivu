<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\entity\Menu\DefaultEntityLocalTaskProvider;
use Drupal\helfi_etusivu\Entity\Search\Form\PromotionForm;
use Drupal\link\LinkItemInterface;
use Drupal\link\LinkTitleVisibility;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Promoted search result for helfi search.
 */
#[ContentEntityType(
  id: 'helfi_search_promotion',
  label: new TranslatableMarkup('Promotion', options: ['context' => 'Helfi search']),
  label_collection: new TranslatableMarkup('Promotions', options: ['context' => 'Helfi search']),
  label_singular: new TranslatableMarkup('promotion', options: ['context' => 'Helfi search']),
  label_plural: new TranslatableMarkup('promotions', options: ['context' => 'Helfi search']),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'bundle',
    'label' => 'title',
    'langcode' => 'langcode',
    'published' => 'status',
    'owner' => 'uid',
  ],
  handlers: [
    'view_builder' => EntityViewBuilder::class,
    'views_data' => EntityViewsData::class,
    'list_builder' => EntityListBuilder::class,
    'access' => EntityAccessControlHandler::class,
    'translation' => ContentTranslationHandler::class,
    'form' => [
      'default' => PromotionForm::class,
      'add' => PromotionForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'edit' => PromotionForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
    "local_task_provider" => [
      "default" => DefaultEntityLocalTaskProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/search',
    'add-form' => '/admin/search/add/{helfi_search_promotion_type}',
    'canonical' => '/admin/search/{helfi_search_promotion}',
    'delete-form' => '/admin/search/{helfi_search_promotion}/delete',
    'edit-form' => '/admin/search/{helfi_search_promotion}/edit',
  ],
  // This entity should be visible only to authenticated users.
  // The canonical path does not contain anything useful for
  // anonymous users. Anonymouse users should interact with
  // promotions through the helfi search.
  admin_permission: "administer search promotions",
  bundle_entity_type: 'helfi_search_promotion_type',
  bundle_label: new TranslatableMarkup('Promotion type', options: ['context' => 'Helfi search']),
  base_table: 'helfi_search_promotion',
  data_table: 'helfi_search_promotion_data',
  translatable: TRUE,
)]
final class Promotion extends ContentEntityBase implements EntityPublishedInterface, EntityOwnerInterface, EntityChangedInterface {

  use EntityPublishedTrait;
  use EntityOwnerTrait;
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += self::publishedBaseFieldDefinitions($entity_type);
    $fields += self::ownerBaseFieldDefinitions($entity_type);

    $fields[$entity_type->getKey('label')] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ]);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Description'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'basic_string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 4,
        ],
      ]);

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(new TranslatableMarkup('Link'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => LinkTitleVisibility::Disabled->value,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => 5,
      ]);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time the promotion was last edited.'))
      ->setTranslatable(TRUE);

    $fields['failed_check_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Failed link checks'))
      ->setDescription(new TranslatableMarkup('Number of consecutive automated link checks that have failed.'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(0)
      ->setReadOnly(TRUE);

    $fields['last_checked'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Last link check'))
      ->setDescription(new TranslatableMarkup('Timestamp of the last automated link check.'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(0)
      ->setReadOnly(TRUE);

    $fields['keywords'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Keywords', options: ['context' => 'Helfi search']))
      ->setDescription(new TranslatableMarkup('This search result is promoted if the query contains any of these keywords. The promotions do not use the AI search.', ['context' => 'Helfi search']))
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    assert($fields[$entity_type->getKey('published')] instanceof BaseFieldDefinition);
    $fields[$entity_type->getKey('published')]
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 15,
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    $original = $this->original ?? NULL;
    if (!$original instanceof self) {
      return;
    }
    $langcode = $this->language()->getId();
    if (!$original->hasTranslation($langcode)) {
      return;
    }
    $originalTranslation = $original->getTranslation($langcode);
    if ($this->get('link')->equals($originalTranslation->get('link'))) {
      return;
    }

    // Clears the automated link check state when the link is edited so the next
    // cron run re-verifies the new URL instead of reusing prior results.
    $this->setLastChecked(0);
    $this->resetFailedCheckCount();
  }

  /**
   * Gets promotion URL.
   */
  public function getUrl(): ?Url {
    foreach ($this->get('link') as $link) {
      if ($link instanceof LinkItem) {
        return $link->getUrl();
      }
    }

    return NULL;
  }

  /**
   * Gets the timestamp of the last automated link check.
   */
  public function getLastChecked(): int {
    return (int) $this->get('last_checked')->value;
  }

  /**
   * Sets the timestamp of the last automated link check.
   */
  public function setLastChecked(int $timestamp): self {
    $this->set('last_checked', $timestamp);
    return $this;
  }

  /**
   * Gets the number of consecutive failed link checks.
   */
  public function getFailedCheckCount(): int {
    return (int) $this->get('failed_check_count')->value;
  }

  /**
   * Resets the failed link check counter back to zero.
   */
  public function resetFailedCheckCount(): self {
    $this->set('failed_check_count', 0);
    return $this;
  }

  /**
   * Increments the failed link check counter by one.
   */
  public function incrementFailedCheckCount(): self {
    $this->set('failed_check_count', $this->getFailedCheckCount() + 1);
    return $this;
  }

  /**
   * Gets promotion links.
   *
   * @return string[]
   *   Array of promotion keywords.
   */
  public function getKeywords(): array {
    $keywords = [];

    foreach ($this->get('keywords') as $keyword) {
      $keywords[] = $keyword->getString();
    }

    return $keywords;
  }

}
