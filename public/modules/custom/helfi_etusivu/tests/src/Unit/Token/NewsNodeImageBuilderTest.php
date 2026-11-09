<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\Token;

use Drupal\Core\Entity\EntityInterface;
use Drupal\helfi_etusivu\Token\NewsNodeImageBuilder;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests which entities the news node image builder accepts.
 */
#[Group('helfi_etusivu')]
#[CoversClass(NewsNodeImageBuilder::class)]
class NewsNodeImageBuilderTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Tests that a missing entity is rejected.
   */
  public function testAppliesRejectsNull() : void {
    $this->assertFalse((new NewsNodeImageBuilder())->applies(NULL));
  }

  /**
   * Tests that a non-node entity is rejected.
   */
  public function testAppliesRejectsNonNode() : void {
    $entity = $this->prophesize(EntityInterface::class)->reveal();

    $this->assertFalse((new NewsNodeImageBuilder())->applies($entity));
  }

  /**
   * Tests that a node is accepted.
   */
  public function testAppliesAcceptsNode() : void {
    $node = $this->prophesize(NodeInterface::class)->reveal();

    $this->assertTrue((new NewsNodeImageBuilder())->applies($node));
  }

}
