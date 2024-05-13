<?php

declare(strict_types=1);

namespace Drupal\helfi_annif;

/**
 * Helper methods for processing keywords.
 */
final class BatchHelper {

  /**
   * Constructs a new instance.
   */
  private function __construct(
    private readonly string $entityType,
    private readonly ?int $batchSize,
    private readonly bool $overwrite,
    private readonly array $entityIds,
  ) {
  }

  /**
   * Creates new batch operation.
   */
  public static function begin(
    string $entityType,
    ?int $batchSize,
    bool $overwrite,
    array $entityIds,
  ) : void {
    $helper = new BatchHelper($entityType, $batchSize, $overwrite, $entityIds);

    $batch_definition = [
      'operations' => [
        [[self::class, 'process'], [$helper]],
      ],
      'progress_message' => t('Completed @percentage% of the operation (@current of @total).'),
    ];

    batch_set($batch_definition);
  }

  /**
   * Processes a batch operation.
   */
  public static function process(BatchHelper $helper, &$context) : void {
    // Check if the sandbox should be initialized.
    if (!isset($context['sandbox']['from'])) {
      $context['sandbox']['from'] = 0;
    }
    // Check if the results should be initialized.
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
    }

    $from = $context['sandbox']['from'];
    $to = min($from + $helper->batchSize, count($helper->entityIds));
    $slice = array_slice($helper->entityIds, $from, $to - $from);

    try {
      $entities = \Drupal::entityTypeManager()
        ->getStorage($helper->entityType)
        ->loadMultiple($slice);

      $keywordManager = \Drupal::service(KeywordManager::class);
      $keywordManager->processEntities($entities, $helper->overwrite);

      $context['results']['processed'] += count($slice);
      $context['sandbox']['from'] = $to;

      // Everything has been processed?
      $context['finished'] = $to >= count($helper->entityIds);
    }
    catch (\Exception $e) {
      $context['message'] = t('An error occurred during processing: @message', ['@message' => $e->getMessage()]);
      $context['finished'] = 1;
    }
  }

}
