<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_annif\Unit\Keyword;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\helfi_annif\Keyword\Keyword;
use Drupal\helfi_annif\Keyword\KeywordGenerator;
use Drupal\helfi_annif\TextConverter\TextConverterInterface;
use Drupal\helfi_annif\TextConverter\TextConverterManager;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

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

    $batch = array_fill(0, KeywordGenerator::MAX_BATCH_SIZE + 1, $this->prophesize(EntityInterface::class)->reveal());

    $this->expectException(\InvalidArgumentException::class);

    $sut->suggestBatch($batch);
  }

  /**
   * Gets service under test.
   */
  private function getSut(ClientInterface $client, TextConverterInterface $converter) : KeywordGenerator {
    $textConverter = new TextConverterManager();
    $textConverter->add($converter);

    return new KeywordGenerator($client, $textConverter);
  }

}
