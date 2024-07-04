<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\helfi_annif\Client\KeywordClientException;
use Drupal\helfi_annif\KeywordManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Keyword processing queue.
 *
 * @QueueWorker(
 *   id = "helfi_annif_queue",
 *   title = @Translation("Keywords queue"),
 *   cron = {"time" = 60}
 * )
 */
final class KeywordQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The keyword manager.
   *
   * @var \Drupal\helfi_annif\KeywordManager
   */
  private KeywordManager $keywordManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger.
   *
   * @var Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    $instance = new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
    $instance->keywordManager = $container->get(KeywordManager::class);
    $instance->entityTypeManager = $container->get(EntityTypeManagerInterface::class);
    $instance->logger = $container->get('logger.channel.helfi_annif');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function processItem(mixed $data) : void {
    if (!isset($data['entity_id'], $data['entity_type'], $data['language'])) {
      return;
    }
    [
      'entity_id' => $id,
      'entity_type' => $type,
      'language' => $language,
      'overwrite' => $overwrite,
    ] = $data;

    $entity = $this
      ->entityTypeManager
      ->getStorage($type)
      ->load($id);

    if ($language && $entity instanceof TranslatableInterface) {
      assert($entity->hasTranslation($language));

      $entity = $entity->getTranslation($language);
    }

    if (!$entity) {
      return;
    }

    try {
      $this->keywordManager->processEntity($entity, overwriteExisting: $overwrite);
    }
    catch (KeywordClientException $exception) {
      $this->logger->error($exception->getMessage());
    }
  }

}
