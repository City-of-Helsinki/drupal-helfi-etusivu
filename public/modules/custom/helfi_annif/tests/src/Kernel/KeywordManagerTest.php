<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_annif\Kernel\TextConverter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\helfi_annif\Client\KeywordClient;
use Drupal\helfi_annif\KeywordManager;
use Drupal\helfi_annif\TextConverter\TextConverterInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\helfi_annif\Traits\AnnifApiTestTrait;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

/**
 * Tests KeywordManager.
 *
 * @group helfi_annif
 */
class KeywordManagerTest extends KernelTestBase {

  use AnnifApiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'text',
    'field',
    'taxonomy',
    'language',
    'helfi_annif',
    'cache_tags.invalidator',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $entities = [
      'taxonomy_term',
    ];

    foreach ($entities as $entity) {
      $this->installEntitySchema($entity);
    }

    $this->installConfig(['helfi_annif']);

    foreach (['fi', 'sv'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  /**
   * Tests queue.
   */
  public function testQueueDeduplication(): void {
    $entity = $this->mockEntity(shouldSave: TRUE);
    $queue = $this->prophesize(QueueInterface::class);
    $queue
      ->createItem(Argument::any())
      ->shouldNotBeCalled();

    $sut = $this->getSut(
      responses: [new Response(200, [], $this->getFixture('suggest.json'))],
      queue: $queue->reveal()
    );

    $sut->processEntity($entity);
    $sut->queueEntity($entity);
  }

  /**
   * Tests large batch.
   */
  public function testLargeBatch(): void {
    // Tests that method handles multiple languages.
    $batch = [
      'foo' => $this->mockEntity(langcode: 'fi', shouldSave: TRUE),
      // Not supported language in batch should not cause errors.
      'foobar' => $this->mockEntity(langcode: 'tr', shouldSave: FALSE),
      'bar' => $this->mockEntity(langcode: 'sv', shouldSave: TRUE),
    ];

    $sut = $this->getSut(responses: [
      new Response(200, [], json_encode([
        json_decode($this->getFixture('suggest.json'), TRUE) + [
          'document_id' => 'foo',
        ],
      ])),
      new Response(200, [], json_encode([
        json_decode($this->getFixture('suggest.json'), TRUE) + [
          'document_id' => 'bar',
        ],
      ])),
    ]);

    $sut->processEntities($batch);
  }

  /**
   * Gets service under test.
   */
  private function getSut(
    array $responses = [],
    ?TextConverterInterface $textConverter = NULL,
    ?QueueInterface $queue = NULL,
  ): KeywordManager {
    $textConverterManager = $this->getTextConverterManager($textConverter);

    $client = new KeywordClient(
      $this->createMockHttpClient($responses),
      $textConverterManager,
    );

    $entityTypeManager = $this->container->get(EntityTypeManagerInterface::class);

    if (!$queue) {
      $queue = $this
        ->prophesize(QueueInterface::class)
        ->reveal();
    }

    $queueFactory = $this->prophesize(QueueFactory::class);
    $queueFactory
      ->get(Argument::any())
      ->willReturn($queue);

    $cacheInvalidator = $this->container->get('cache_tags.invalidator');

    return new KeywordManager(
      $entityTypeManager,
      $client,
      $queueFactory->reveal(),
      $cacheInvalidator
    );
  }

}
