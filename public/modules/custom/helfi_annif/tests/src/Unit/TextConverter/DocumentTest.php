<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_annif\Unit\TextConverter;

use Drupal\Core\Render\Markup;
use Drupal\helfi_annif\TextConverter\Document;
use Drupal\Tests\UnitTestCase;

/**
 * Tests text converter.
 *
 * @group helfi_annif
 */
class DocumentTest extends UnitTestCase {

  /**
   * Tests ::stripNodes.
   */
  public function testStripNodes() : void {
    $needle = "Main content";

    $sut = new Document(Markup::create(
      "<article><div class='visually-hidden'>$needle</div><h1>Hello, world!</h1></article>"
    ));

    $this->assertStringContainsString($needle, $sut);
    $sut->stripNodes("//*[contains(@class, 'visually-hidden')]");
    $this->assertStringNotContainsString($needle, $sut);
  }

  /**
   * Tests invalid XPath.
   */
  public function testInvalidXPath() : void {
    $sut = new Document(Markup::create(
      "<article><div class='visually-hidden'></div><h1>Hello, world!</h1></article>"
    ));

    // Should not throw.
    $sut->stripNodes("//*[contains(@class, 'does-not-exists')]");

    $this->expectException(\InvalidArgumentException::class);

    // Removing attributes is not useful.
    $sut->stripNodes("//*[contains(@class, 'visually-hidden')]/@class");
  }

}
