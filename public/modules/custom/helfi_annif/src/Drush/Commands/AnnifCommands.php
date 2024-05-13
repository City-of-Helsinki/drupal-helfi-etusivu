<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Drush\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\helfi_annif\Client\KeywordClient;
use Drupal\helfi_annif\KeywordManager;
use Drupal\helfi_annif\TextConverter\TextConverterManager;
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

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\helfi_annif\TextConverter\TextConverterManager $textConverter
   *   The text converter.
   * @param \Drupal\helfi_annif\KeywordManager $keywordManager
   *   The keyword generator.
   */
  public function __construct(
    private readonly Connection $connection,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly TextConverterManager $textConverter,
    private readonly KeywordManager $keywordManager,
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
      'batch-size' => KeywordClient::MAX_BATCH_SIZE,
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
      ->addOperation([self::class, 'processBatch'], [
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
  public static function processBatch(
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
      $entities = \Drupal::entityTypeManager()
        ->getStorage($entityType)
        ->loadMultiple($slice);

      $keywordManager = \Drupal::service(KeywordManager::class);
      $keywordManager->processEntities($entities, $overwrite);

      $context['sandbox']['from'] = $to;
      $context['message'] = t("@total entities remaining", [
        '@total' => count($entityIds) - $to,
      ]);

      // Everything has been processed?
      $context['finished'] = $to >= count($entityIds);
    }
    catch (\Exception $e) {
      $context['message'] = t('An error occurred during processing: @message', ['@message' => $e->getMessage()]);
      $context['finished'] = 1;
    }
  }

  /**
   * Preview entity text conversion result.
   *
   * @param string $entityType
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
  public function preview(string $entityType, string $id, array $options = ['language' => NULL]) : int {
    try {
      $entity = $this->entityTypeManager
        ->getStorage($entityType)
        ->load($id);

      if (
        !empty($options['language']) &&
        $entity instanceof TranslatableInterface &&
        $entity->hasTranslation($options['language'])
      ) {
        $entity = $entity->getTranslation($options['language']);
      }

      if ($entity && $content = $this->textConverter->convert($entity)) {
        $this->io()->text($content);

        return DrushCommands::EXIT_SUCCESS;
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException) {
    }

    return DrushCommands::EXIT_FAILURE;
  }

}
