<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_etusivu\Entity\Search\ContactDetails;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\taxonomy\TermInterface;

/**
 * Indexes the organization hierarchy of contact details.
 */
#[SearchApiProcessor(
  id: 'helfi_contact_organization_hierarchy',
  label: new TranslatableMarkup('Contact organization hierarchy'),
  description: new TranslatableMarkup('Indexes the organization hierarchy of contact details as objects.'),
  stages: [
    'add_properties' => 0,
  ],
)]
final class ContactOrganizationHierarchy extends ProcessorPluginBase {

  /**
   * The property name.
   */
  private const string PROPERTY = 'organization_hierarchy';

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index): bool {
    return array_any($index->getDatasources(), fn($datasource) => $datasource->getEntityTypeId() === 'helfi_search_contact');
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL): array {
    if ($datasource?->getEntityTypeId() !== 'helfi_search_contact') {
      return [];
    }

    return [
      self::PROPERTY => new ProcessorProperty([
        'label' => $this->t('Organization hierarchy'),
        'description' => $this->t('The organizations from the root to the organization of the contact.'),
        'type' => 'object',
        'processor_id' => $this->getPluginId(),
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param \Drupal\search_api\Item\ItemInterface<mixed> $item
   */
  public function addFieldValues(ItemInterface $item): void {
    $entity = $item->getOriginalObject()?->getValue();

    if (!$entity instanceof ContactDetails || !$organization = $entity->getOrganization()) {
      return;
    }

    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), $item->getDatasourceId(), self::PROPERTY);

    if (!$fields) {
      return;
    }

    $langcode = $entity->language()->getId();
    $hierarchy = $this->getHierarchy($organization);

    foreach ($fields as $field) {
      foreach ($hierarchy as $term) {
        if ($term->hasTranslation($langcode)) {
          $term = $term->getTranslation($langcode);
        }

        $field->addValue([
          'id' => (int) $term->id(),
          'name' => $term->label(),
        ]);
      }
    }
  }

  /**
   * Gets the term and its parents.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The terms, starting from the root.
   */
  private function getHierarchy(TermInterface $term): array {
    $hierarchy = [];

    while ($term instanceof TermInterface && !isset($hierarchy[$term->id()])) {
      $hierarchy[$term->id()] = $term;
      $term = $term->get('parent')->entity;
    }

    return array_reverse(array_values($hierarchy));
  }

}
