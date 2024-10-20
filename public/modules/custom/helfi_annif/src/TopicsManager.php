<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\helfi_annif\Client\ApiClient;
use Drupal\helfi_annif\Client\Keyword;

/**
 * The topic manager.
 */
final class TopicsManager {

  public const TOPICS_FIELD = 'annif_suggested_topics';
  public const KEYWORD_VID = 'annif_keywords';

  /**
   * Taxonomy term storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private readonly EntityStorageInterface $termStorage;

  /**
   * List of items that have been processed in this request.
   *
   * @var array<string, TRUE>
   */
  private array $processedItems = [];

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\helfi_annif\Client\ApiClient $keywordGenerator
   *   The keyword generator.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ApiClient $keywordGenerator,
    private readonly QueueFactory $queueFactory,
  ) {
    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * Gets key for $processedItems.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  private function getEntityKey(EntityInterface $entity) : string {
    return implode(":", [$entity->getEntityTypeId(), $entity->bundle(), $entity->language()->getId()]);
  }

  /**
   * Returns true if entity has been processed in this request.
   *
   * This can be used to prevent recursion if items are processed
   * in hook_entity_update.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  private function isEntityProcessed(EntityInterface $entity) : bool {
    return isset($this->processedItems[$this->getEntityKey($entity)]);
  }

  /**
   * Queues keyword generation for single entity.
   *
   * @param \Drupal\helfi_annif\RecommendableInterface $entity
   *   The entity.
   * @param bool $overwriteExisting
   *   Overwrites existing keywords when set to TRUE.
   */
  public function queueEntity(RecommendableInterface $entity, bool $overwriteExisting = FALSE) : void {
    if (
      !$entity->hasField(self::TOPICS_FIELD) ||
      // Skip if entity was processed in this request.
      $this->isEntityProcessed($entity) ||
      // Skip if entity already has keywords.
      (!$overwriteExisting && $entity->hasKeywords())
    ) {
      return;
    }

    $this->queueFactory
      ->get('helfi_annif_queue')
      ->createItem([
        'entity_id' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'language' => $entity->language()->getId(),
        'overwrite' => $overwriteExisting,
      ]);
  }

  /**
   * Generates keywords for single entity.
   *
   * @param \Drupal\helfi_annif\RecommendableInterface $entity
   *   The entity.
   * @param bool $overwriteExisting
   *   Overwrites existing keywords when set to TRUE.
   *
   * @throws \Drupal\helfi_annif\Client\ApiClientException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processEntity(RecommendableInterface $entity, bool $overwriteExisting = FALSE) : void {
    if (!$entity->hasField(self::TOPICS_FIELD)) {
      return;
    }

    // Skip if entity already has keywords.
    if (!$overwriteExisting && $entity->hasKeywords()) {
      return;
    }

    $keywords = $this->keywordGenerator->suggest($entity);
    if (!$keywords) {
      return;
    }

    $this->saveKeywords($entity, $keywords);
  }

  /**
   * Generates keywords for entities.
   *
   * @param \Drupal\helfi_annif\RecommendableInterface[] $entities
   *   The entities.
   * @param bool $overwriteExisting
   *   Overwrites existing keywords when set to TRUE.
   *
   * @throws \Drupal\helfi_annif\Client\ApiClientException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processEntities(array $entities, bool $overwriteExisting = FALSE) : void {
    foreach ($this->prepareBatches($entities, $overwriteExisting) as $batch) {
      $result = $this->keywordGenerator->suggestBatch($batch);

      // KeywordGenerator::suggestBatch preserves ids.
      foreach ($result as $id => $keywords) {
        if (!$keywords) {
          continue;
        }

        $this->saveKeywords($batch[$id], $keywords);
      }
    }
  }

  /**
   * Gets entities in batches.
   *
   * @param \Drupal\helfi_annif\RecommendableInterface[] $entities
   *   The entities.
   * @param bool $overwriteExisting
   *   Overwrites existing keywords when set to TRUE.
   *
   * @return \Generator
   *   Batch of entities.
   */
  private function prepareBatches(array $entities, bool $overwriteExisting) : \Generator {
    // Keyword generator does not support mixing languages in one request,
    // so we divide translations into buckets that are handled separately.
    // Each bucket size must be <= KeywordGenerator::MAX_BATCH_SIZE.
    $buckets = [];

    foreach ($entities as $key => $entity) {
      assert($entity instanceof RecommendableInterface);
      if (!$entity->hasField(self::TOPICS_FIELD)) {
        continue;
      }

      // Skip if entity already has keywords.
      if (!$overwriteExisting  && $entity->hasKeywords()) {
        continue;
      }

      if ($entity instanceof TranslatableInterface) {
        foreach ($entity->getTranslationLanguages() as $language) {
          $buckets[$language->getId()][$key] = $entity->getTranslation($language->getId());
        }
      }
      else {
        $buckets[$entity->language()->getId()][$key] = $entity;
      }
    }

    foreach ($buckets as $bucket) {
      foreach (array_chunk($bucket, ApiClient::MAX_BATCH_SIZE, preserve_keys: TRUE) as $batch) {
        yield $batch;
      }
    }
  }

  /**
   * Saves keywords to entity.
   *
   * @param \Drupal\helfi_annif\RecommendableInterface $entity
   *   The entity.
   * @param \Drupal\helfi_annif\Client\Keyword[] $keywords
   *   Keywords.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function saveKeywords(RecommendableInterface $entity, array $keywords) : void {
    $values = array_map(fn($keyword) => [
      'entity' => $this->getTerm($keyword, $entity->language()->getId()),
      'score' => $keyword->score,
    ], $keywords);

    $field = $entity->get(self::TOPICS_FIELD);
    assert($field instanceof EntityReferenceFieldItemListInterface);
    foreach ($field->referencedEntities() as $topicsEntity) {
      /** @var \Drupal\helfi_annif\SuggestedTopicsInterface $topicsEntity */
      $topicsEntity->set('keywords', $values);
      $topicsEntity->save();
    }

    // Mark as processed so the same entity is bombarding the
    // API if it is queued multiple times for some reason.
    $this->processedItems[$this->getEntityKey($entity)] = TRUE;
  }

  /**
   * Gets or inserts taxonomy term that matches API result.
   *
   * @param \Drupal\helfi_annif\Client\Keyword $keyword
   *   Keyword.
   * @param string $langcode
   *   Term langcode.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function getTerm(Keyword $keyword, string $langcode) {
    $terms = $this->termStorage->loadByProperties([
      'vid' => self::KEYWORD_VID,
      // Unique identifier for keyword.
      'field_uri' => $keyword->uri,
    ]);

    if ($term = reset($terms)) {
      /** @var \Drupal\taxonomy\TermInterface $term */
      if ($term->hasTranslation($langcode)) {
        return $term->getTranslation($langcode);
      }

      $term = $term->addTranslation($langcode, [
        'vid' => self::KEYWORD_VID,
        'name' => $keyword->label,
        'langcode' => $langcode,
        'field_uri' => $keyword->uri,
      ]);
    }
    else {
      $term = $this->termStorage->create([
        'vid' => self::KEYWORD_VID,
        'name' => $keyword->label,
        'langcode' => $langcode,
        'field_uri' => $keyword->uri,
      ]);
    }

    $term->save();

    return $term;
  }

}
