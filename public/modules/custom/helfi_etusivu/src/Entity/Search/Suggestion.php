<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_etusivu\Entity\Search\Form\SuggestionForm;
use Drupal\views\EntityViewsData;

/**
 * An example search term shown near search inputs.
 *
 * The ordering lives in the {draggableviews_structure} table.
 *
 * @see \Drupal\helfi_etusivu\Search\Controller\SearchSuggestionsController
 */
#[ContentEntityType(
  id: 'helfi_search_suggestion',
  label: new TranslatableMarkup('Search suggestion', options: ['context' => 'Helfi search']),
  label_collection: new TranslatableMarkup('Search suggestions', options: ['context' => 'Helfi search']),
  label_singular: new TranslatableMarkup('search suggestion', options: ['context' => 'Helfi search']),
  label_plural: new TranslatableMarkup('search suggestions', options: ['context' => 'Helfi search']),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'suggestion',
    'langcode' => 'langcode',
  ],
  handlers: [
    'views_data' => EntityViewsData::class,
    'list_builder' => EntityListBuilder::class,
    'access' => EntityAccessControlHandler::class,
    'translation' => SuggestionTranslationHandler::class,
    'form' => [
      'default' => SuggestionForm::class,
      'add' => SuggestionForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'edit' => SuggestionForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/search/suggestions',
    'add-form' => '/admin/search/suggestions/add',
    'delete-form' => '/admin/search/suggestions/{helfi_search_suggestion}/delete',
    'edit-form' => '/admin/search/suggestions/{helfi_search_suggestion}/edit',
    // The entity does not have `canonical` route, since we don't want
    // these entities to have separate pages. Without it `content_translation`
    // module does not generate translation routes. See
    // Drupal\content_translation\Hook\ContentTranslationHooks::entityTypeAlter.
    'drupal:content-translation-overview' => '/admin/search/suggestions/{helfi_search_suggestion}/translations',
    'drupal:content-translation-add' => '/admin/search/suggestions/{helfi_search_suggestion}/translations/add/{source}/{target}',
    'drupal:content-translation-edit' => '/admin/search/suggestions/{helfi_search_suggestion}/translations/edit/{language}',
    'drupal:content-translation-delete' => '/admin/search/suggestions/{helfi_search_suggestion}/translations/delete/{language}',
  ],
  admin_permission: 'administer search content',
  base_table: 'helfi_search_suggestion',
  data_table: 'helfi_search_suggestion_data',
  translatable: TRUE,
  additional: [
    'translation' => [
      'content_translation' => [
        'access_callback' => 'content_translation.manager:access',
      ],
    ],
  ],
)]
final class Suggestion extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields[(string) $entity_type->getKey('label')] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Search term', options: ['context' => 'Helfi search']))
      ->setDescription(new TranslatableMarkup('The example search term shown to users.', options: ['context' => 'Helfi search']))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 256)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    return $fields;
  }

  /**
   * Gets the search term.
   *
   * @return string
   *   The term used as the search query.
   */
  public function getSuggestion(): string {
    return (string) $this->get('suggestion')->value;
  }

}
