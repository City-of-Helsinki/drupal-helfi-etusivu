<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_annif\Unit\TextConverter;

use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\helfi_annif\Client\KeywordClient;
use Drupal\helfi_annif\KeywordManager;
use Drupal\helfi_annif\TextConverter\TextConverterInterface;
use Drupal\Tests\helfi_annif\Traits\AnnifApiTestTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests KeywordManager.
 *
 * @group helfi_annif
 */
class KeywordManagerTest extends UnitTestCase {

  use AnnifApiTestTrait;

  /**
   * Tests entities without keyword field.
   */
  public function testUnsupportedEntity(): void {
    // hasField(KeywordManager::KEYWORD_FIELD) for entity is FALSE.
    $entity = $this->mockEntity(hasKeywords: NULL);
    $queue = $this->prophesize(QueueInterface::class);
    $queue
      ->createItem(Argument::any())
      ->shouldNotBeCalled();

    $sut = $this->getSut(queue: $queue->reveal());

    $sut->queueEntity($entity);
    $sut->processEntity($entity);
    $sut->processEntities([$entity]);

    $sut->queueEntity($entity, TRUE);
    $sut->processEntity($entity, TRUE);
    $sut->processEntities([$entity], TRUE);
  }

  /**
   * Tests entities without keyword field.
   */
  public function testKeywordOverwriting(): void {
    $entity = $this->mockEntity(hasKeywords: TRUE);
    $queue = $this->prophesize(QueueInterface::class);
    $queue
      ->createItem(Argument::any())
      ->shouldNotBeCalled();

    $sut = $this->getSut(queue: $queue->reveal());

    $sut->queueEntity($entity);
    $sut->processEntity($entity);
    $sut->processEntities([$entity]);
  }

  /**
   * Tests entities with unsupported langcode.
   */
  public function testUnsupportedLangcode(): void {
    // hasField(KeywordManager::KEYWORD_FIELD) for entity is FALSE.
    $entity = $this->mockEntity(langcode: 'xzz', shouldSave: FALSE);
    $sut = $this->getSut();

    $sut->processEntity($entity);
    $sut->processEntities([$entity]);
  }

  /**
   * Tests queue.
   */
  public function testQueue(): void {
    $entity = $this->mockEntity();
    $queue = $this->prophesize(QueueInterface::class);
    $queue
      ->createItem(Argument::any())
      ->shouldBeCalled();

    $sut = $this->getSut(queue: $queue->reveal());

    $sut->queueEntity($entity);
  }

  /**
   * Gets service under test.
   */
  private function getSut(
    array $responses = [],
    ?TextConverterInterface $textConverter = NULL,
    ?EntityStorageInterface $termStorage = NULL,
    ?QueueInterface $queue = NULL,
  ): KeywordManager {
    $textConverterManager = $this->getTextConverterManager($textConverter);

    $client = new KeywordClient(
      $this->createMockHttpClient($responses),
      $textConverterManager,
    );

    if (!$termStorage) {
      $termStorage = $this
        ->prophesize(EntityStorageInterface::class)
        ->reveal();
    }

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager
      ->getStorage(Argument::any())
      ->willReturn($termStorage);

    if (!$queue) {
      $queue = $this
        ->prophesize(QueueInterface::class)
        ->reveal();
    }

    $queueFactory = $this->prophesize(QueueFactory::class);
    $queueFactory
      ->get(Argument::any())
      ->willReturn($queue);

    $cacheInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);
    $cacheInvalidator
      ->invalidateTags(['taxonomy_term:1234']);

    return new KeywordManager(
      $entityTypeManager->reveal(),
      $client,
      $queueFactory->reveal(),
      $cacheInvalidator->reveal(),
    );
  }

}
