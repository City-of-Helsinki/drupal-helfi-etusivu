<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Plugin\search_api\processor;

use Drupal\helfi_annif\Keyword\KeywordGenerator;
use Drupal\helfi_annif\Keyword\KeywordGeneratorException;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds AI suggested subjects to the indexed data.
 *
 * @SearchApiProcessor(
 *    id = "keyword_generator",
 *    label = @Translation("AI keywords"),
 *    description = @Translation("Adds AI generated keywords to the indexed data."),
 *    stages = {
 *      "add_properties" = 0,
 *      "alter_items" = 0,
 *    },
 *    locked = true,
 *    hidden = true,
 *  )
 */
final class KeywordProcessor extends ProcessorPluginBase {


  /**
   * Keywords property name.
   *
   * @see \Drupal\search_api\Processor\ProcessorInterface::getPropertyDefinitions
   *
   * @var string
   */
  private const KEYWORDS_PROPERTY_NAME = "ai_keywords";

  /**
   * The recommendation manager.
   *
   * @var \Drupal\helfi_annif\Keyword\KeywordGenerator
   */
  private KeywordGenerator $keywordGenerator;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->keywordGenerator = $container->get(KeywordGenerator::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) : array {
    $properties = [];

    if (!$datasource) {
      $properties[self::KEYWORDS_PROPERTY_NAME] = new ProcessorProperty([
        'label' => $this->t('Keywords'),
        'description' => $this->t('Generated keywords'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ]);
    }

    return $properties;
  }

  /**
   * {@inheritDoc}
   *
   * Process field values in batches.
   *
   * @todo Tests! UHF-9977.
   */
  public function alterIndexedItems(array &$items) : void {
    $buckets = $this->splitItemsByLangcode($items);

    // Keyword generation must be done separately for each language.
    foreach ($buckets as $entities) {
      foreach (array_chunk($entities, KeywordGenerator::MAX_BATCH_SIZE, TRUE) as $batch) {
        $this->processSuggestionBatch($items, $batch);
      }
    }
  }

  /**
   * Split items by langcode.
   *
   * @param array $items
   *   Items.
   *
   * @return array
   *   Items split by langcode.
   */
  private function splitItemsByLangcode(array &$items) : array {
    $buckets = [];

    foreach ($items as $key => $item) {
      try {
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity = $item->getOriginalObject()->getValue();
      }
      catch (SearchApiException) {
        unset($items[$key]);
        continue;
      }

      $buckets[$entity->language()->getId()][$key] = $entity;
    }

    return $buckets;
  }

  /**
   * Process batch of items.
   *
   * @param \Drupal\search_api\Item\ItemInterface[] &$items
   *   All items.
   * @param \Drupal\Core\Entity\EntityInterface[] $batch
   *   Batch of entities.
   */
  private function processSuggestionBatch(array &$items, array $batch) : void {
    assert(count($batch) <= KeywordGenerator::MAX_BATCH_SIZE);

    $results = [];

    try {
      // @todo Override with prefetched / reviewed results: UHF-9964
      $results = $this->keywordGenerator->suggestBatch($batch);

      foreach ($results as $key => $keywords) {
        if (!$item = $items[$key]) {
          throw new \LogicException("Item should exists");
        }

        $fields = $item->getFields();
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($fields, NULL, self::KEYWORDS_PROPERTY_NAME);

        foreach ($fields as $field) {
          $field->addValue($keywords);
        }
      }
    }
    catch (KeywordGeneratorException) {
    }

    // Skip items that did not produce any results.
    foreach (array_diff_key($batch, $results) as $key => $item) {
      unset($items[$key]);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see self::alterIndexedItems
   */
  public function addFieldValues(ItemInterface $item) : void {
  }

}
