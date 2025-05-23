<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Drush\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Error;
use Drupal\helfi_annif\Client\ApiClient;
use Drupal\helfi_annif\ReferenceUpdater;
use Drupal\helfi_annif\TextConverter\TextConverterManager;
use Drupal\helfi_annif\TopicsManager;
use Drush\Attributes\Argument;
use Drush\Attributes\Command;
use Drush\Attributes\Option;
use Drush\Attributes\Usage;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
final class AnnifCommands extends DrushCommands {

  use AutowireTrait;
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\helfi_annif\TextConverter\TextConverterManager $textConverter
   *   The text converter.
   * @param \Drupal\helfi_annif\TopicsManager $topicsManager
   *   The keyword generator.
   * @param \Drupal\helfi_annif\ReferenceUpdater $referenceManager
   *   The reference manager.
   */
  public function __construct(
    private readonly Connection $connection,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly TextConverterManager $textConverter,
    private readonly TopicsManager $topicsManager,
    private readonly ReferenceUpdater $referenceManager,
  ) {
    parent::__construct();
  }

  /**
   * Generate keyword to entities.
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param array $options
   *   The command options.
   *
   * @return int
   *   The exit code.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  #[Command(name: 'helfi:generate-keywords')]
  #[Argument(name: 'entityType', description: 'Entity type')]
  #[Argument(name: 'bundle', description: 'Entity bundle')]
  #[Option(name: 'overwrite', description: 'Overwrites existing keywords (use with caution)')]
  #[Option(name: 'batch-size', description: 'Batch size')]
  #[Usage(name: 'drush helfi:generate-keywords node news_item', description: 'Generate keywords for news items.')]
  public function process(
    string $entityType,
    string $bundle,
    array $options = [
      'overwrite' => FALSE,
      'batch-size' => ApiClient::MAX_BATCH_SIZE,
    ],
  ) : int {
    $definition = $this->entityTypeManager->getDefinition($entityType);
    if (!$definition) {
      $this->io()->writeln('Given entity type is not supported.');
      return DrushCommands::EXIT_FAILURE;
    }

    $query = $this->connection
      ->select($definition->getBaseTable(), 't')
      ->fields('t', [$definition->getKey('id')])
      ->condition($definition->getKey('bundle'), $bundle);

    $entityIds = $query
      ->execute()
      ->fetchCol();

    $batch = (new BatchBuilder())
      ->addOperation([$this, 'processBatch'], [
        $entityType,
        $options['batch-size'],
        $options['overwrite'],
        $entityIds,
      ]);

    batch_set($batch->toArray());

    drush_backend_batch_process();

    return DrushCommands::EXIT_SUCCESS;
  }

  /**
   * Processes a batch operation.
   */
  public function processBatch(
    string $entityType,
    ?int $batchSize,
    bool $overwrite,
    array $entityIds,
    &$context,
  ) : void {
    // Check if the sandbox should be initialized.
    if (!isset($context['sandbox']['from'])) {
      $context['sandbox']['from'] = 0;
    }

    $from = $context['sandbox']['from'];
    $to = min($from + $batchSize, count($entityIds));
    $slice = array_slice($entityIds, $from, $to - $from);

    try {
      $entities = $this->entityTypeManager
        ->getStorage($entityType)
        ->loadMultiple($slice);

      $this->topicsManager->processEntities($entities, $overwrite);

      $context['sandbox']['from'] = $to;
      $context['message'] = $this->t("@total entities remaining", [
        '@total' => count($entityIds) - $to,
      ]);

      // Everything has been processed?
      $context['finished'] = $to >= count($entityIds);
    }
    catch (\Exception $e) {
      $context['message'] = $this->t('An error occurred during processing: @message', ['@message' => $e->getMessage()]);
      $context['finished'] = 1;
    }
  }

  /**
   * Preview entity text conversion result.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $id
   *   The entity id.
   * @param array $options
   *   Command options.
   *
   * @return int
   *   The exit code.
   */
  #[Command(name: 'helfi:preview-text')]
  #[Argument(name: 'entity_type', description: 'Entity type')]
  #[Argument(name: 'id', description: 'Entity id')]
  #[Option(name: 'language', description: 'Entity language', suggestedValues: ['fi', 'sv', 'en'])]
  #[Usage(name: 'drush helfi:preview-text node 123', description: 'Preview node with id 123.')]
  #[Usage(name: 'drush helfi:preview-text node 123 --language sv', description: 'Preview swedish translation of node 123.')]
  public function preview(string $entity_type, string $id, array $options = ['language' => NULL]) : int {
    try {
      $entity = $this->entityTypeManager
        ->getStorage($entity_type)
        ->load($id);

      if (!$entity) {
        $this->io->error("Failed to load $entity_type:$id");
        return self::EXIT_FAILURE;
      }

      if (
        !empty($options['language']) &&
        $entity instanceof TranslatableInterface &&
        $entity->hasTranslation($options['language'])
      ) {
        $entity = $entity->getTranslation($options['language']);
      }

      if ($content = $this->textConverter->convert($entity)) {
        $this->io()->text($content);

        return DrushCommands::EXIT_SUCCESS;
      }
      else {
        $this->io()->error("Failed to find text converter for $entity_type:$id");
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      Error::logException($this->logger(), $e);
    }

    return DrushCommands::EXIT_FAILURE;
  }

  /**
   * Set new fields' default values.
   */
  #[Command(name: 'helfi:annif-entity-defaults')]
  public function setEntityDefaultAnnifValues(): int {

    $query = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', ['news_item', 'news_article'], 'in');

    $batch = (new BatchBuilder())
      ->addOperation([$this, 'batchVisibilityFieldsDefaultValues'], [
        $query->execute(),
      ]);

    batch_set($batch->toArray());

    drush_backend_batch_process();

    return DrushCommands::EXIT_SUCCESS;
  }

  /**
   * Set the block and recommendation visibility for all nodes.
   *
   * @param array $entityIds
   *   Ids of entities to update.
   * @param array $context
   *   The context.
   */
  public function batchVisibilityFieldsDefaultValues(array $entityIds, &$context,): void {
    $batchSize = 200;

    // Check if the sandbox should be initialized.
    if (!isset($context['sandbox']['from'])) {
      $context['sandbox']['from'] = 0;
    }

    $from = $context['sandbox']['from'];
    $to = min($from + $batchSize, count($entityIds));
    $slice = array_slice($entityIds, $from, $to - $from);

    try {
      $entities = $this->entityTypeManager
        ->getStorage('node')
        ->loadMultiple($slice);

      foreach ($entities as $entity) {
        $entity->set('in_recommendations', 1);
        $entity->set('show_annif_block', 1);
        // @todo Prevent updating updated time when saving.
        $entity->save();
      }

      $context['sandbox']['from'] = $to;
      $context['message'] = $this->t("@total entities remaining", [
        '@total' => count($entityIds) - $to,
      ]);

      // Everything has been processed?
      $context['finished'] = $to >= count($entityIds);
    }
    catch (\Exception $e) {
      $context['message'] = $this->t('An error occurred during processing: @message', ['@message' => $e->getMessage()]);
      $context['finished'] = 1;
    }
  }

  /**
   * Fix entity references in a batch.
   *
   * @param array $entityIds
   *   Ids of entities to update.
   * @param array $context
   *   The context.
   */
  public function batchFixEntityReferences(array $entityIds, array &$context): void {
    if (!isset($context['sandbox']['from'])) {
      $context['sandbox']['from'] = 0;
    }

    $batchSize = 50;
    $from = $context['sandbox']['from'];
    $to = min($from + $batchSize, count($entityIds));
    $slice = array_slice($entityIds, $from, $to - $from);

    try {
      foreach ($slice as $item) {
        ['entity_type' => $entity_type, 'id' => $id] = $item;

        $entity = $this->entityTypeManager
          ->getStorage($entity_type)
          ->load($id);

        assert($entity instanceof FieldableEntityInterface);
        $this->referenceManager->updateEntityReferenceFields($entity);
      }

      $context['sandbox']['from'] = $to;
      $context['message'] = sprintf("%d entities remaining", count($entityIds) - $to);
      $context['finished'] = $to >= count($entityIds);
    }
    catch (\Exception $e) {
      $context['message'] = sprintf('An error occurred during processing: %s', $e->getMessage());
      $context['finished'] = 1;
    }
  }

  /**
   * Set new fields' default values.
   */
  #[Command(name: 'helfi:annif-fix-references')]
  public function fixEntityReferences(): int {
    $entities = $this->referenceManager->getReferencesWithoutTarget();

    $batch = (new BatchBuilder())
      ->addOperation([$this, 'batchFixEntityReferences'], [
        $entities,
      ]);

    batch_set($batch->toArray());

    drush_backend_batch_process();

    return DrushCommands::EXIT_SUCCESS;
  }

}
