<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\entity\Menu\DefaultEntityLocalTaskProvider;
use Drupal\entity\Menu\EntityCollectionLocalActionProvider;
use Drupal\helfi_etusivu\Entity\Search\Form\PromotionForm;
use Drupal\helfi_etusivu\Entity\Search\Listing\ListBuilder;
use Drupal\link\LinkItemInterface;
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
    'label' => 'title',
    'langcode' => 'langcode',
    'published' => 'status',
    'owner' => 'uid',
  ],
  handlers: [
    'list_builder' => ListBuilder::class,
    'view_builder' => EntityViewBuilder::class,
    'views_data' => EntityViewsData::class,
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
    "local_action_provider" => [
      "collection" => EntityCollectionLocalActionProvider::class,
    ],
    "local_task_provider" => [
      "default" => DefaultEntityLocalTaskProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/search',
    'add-form' => '/admin/search/add',
    'canonical' => '/admin/search/{helfi_search_promotion}',
    'delete-form' => '/admin/search/{helfi_search_promotion}/delete',
    'edit-form' => '/admin/search/{helfi_search_promotion}/edit',
  ],
  // This entity should be visible only to authenticated users.
  // The canonical path does not contain anything useful for
  // anonymous users. Anonymouse users should interact with
  // promotions through the helfi search.
  admin_permission: "administer search promotions",
  base_table: 'helfi_search_promotion',
  data_table: 'helfi_search_promotion_data',
  translatable: TRUE,
)]
final class Promotion extends ContentEntityBase implements EntityPublishedInterface, EntityOwnerInterface {

  use EntityPublishedTrait;
  use EntityOwnerTrait;

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

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Description'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 0,
      ]);

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(new TranslatableMarkup('Link'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_DISABLED,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => 5,
      ]);

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
