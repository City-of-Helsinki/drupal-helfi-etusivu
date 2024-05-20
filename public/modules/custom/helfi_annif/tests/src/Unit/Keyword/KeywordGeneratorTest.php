<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_annif\Unit\Keyword;

use Drupal\Core\Entity\EntityInterface;
use Drupal\helfi_annif\Client\KeywordClient;
use Drupal\helfi_annif\TextConverter\TextConverterInterface;
use Drupal\helfi_annif\TextConverter\TextConverterManager;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests keyword generator.
 *
 * @group helfi_annif
 */
class KeywordGeneratorTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Asserts that maximum batch size is not accepted.
   */
  public function testMaxBatchSize() : void {
    $sut = $this->getSut(
      $this->prophesize(ClientInterface::class)->reveal(),
      $this->prophesize(TextConverterInterface::class)->reveal(),
    );

    $batch = array_fill(0, KeywordClient::MAX_BATCH_SIZE + 1, $this->prophesize(EntityInterface::class)->reveal());

    $this->expectException(\InvalidArgumentException::class);

    $sut->suggestBatch($batch);
  }

  /**
   * Gets service under test.
   */
  private function getSut(ClientInterface $client, TextConverterInterface $converter) : KeywordClient {
    $textConverter = new TextConverterManager();
    $textConverter->add($converter);

    return new KeywordClient($client, $textConverter);
  }

}