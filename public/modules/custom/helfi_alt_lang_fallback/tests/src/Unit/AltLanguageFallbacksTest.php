<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_alt_lang_fallback\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Url;
use Drupal\helfi_alt_lang_fallback\AltLanguageFallbacks;
use Drupal\helfi_api_base\Language\DefaultLanguageResolverInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests Composer plugin.
 *
 * @coversDefaultClass \Drupal\helfi_alt_lang_fallback\AltLanguageFallbacks
 * @group helfi_alt_lang_fallback
 */
class AltLanguageFallbacksTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Gets the SUT.
   *
   * @param bool $isAltLanguage
   *   Whether current language is alt language.
   *
   * @return \Drupal\helfi_alt_lang_fallback\AltLanguageFallbacks
   *   The sut.
   */
  public function getSut(bool $isAltLanguage) : AltLanguageFallbacks {
    $resolver = $this->prophesize(DefaultLanguageResolverInterface::class);
    $resolver->isAltLanguage()->willReturn($isAltLanguage);
    $container = new ContainerBuilder();
    $container->set('language_manager', $this->prophesize(LanguageManagerInterface::class)->reveal());
    $container->set('entity_type.manager', $this->prophesize(EntityTypeManagerInterface::class)->reveal());
    $container->set('menu.link_tree', $this->prophesize(MenuLinkTreeInterface::class)->reveal());
    $container->set('helfi_api_base.default_language_resolver', $resolver->reveal());
    return AltLanguageFallbacks::create($container);
  }

  /**
   * Tests all methods with invalid alt language.
   */
  public function testInvalidAltLanguage() {
    $sut = $this->getSut(FALSE);
    $this->assertFalse($sut->shouldAttributesBeAddedToBlock('header_top'));
    $this->assertFalse($sut->shouldAttributesBeAddedToBlock('menu_block_current_language:header-top-navigation'));
    $this->assertFalse($sut->checkIfBlockHasFallbackContent([]));
    $this->assertFalse($sut->shouldMenuTreeBeReplaced('headertopnavigation', []));
  }

  /**
   * Tests ::shouldAttributesBeAddedToRegion().
   */
  public function testTestShouldAttributesBeAddedToRegion(): void {
    $sut = $this->getSut(TRUE);
    // Test with invalid region.
    $this->assertFalse($sut->shouldAttributesBeAddedToRegion('region'));
    // Test with correct region.
    $this->assertTrue($sut->shouldAttributesBeAddedToRegion('header_top'));
  }

  /**
   * Tests ::shouldAttributesBeAddedToBlock().
   */
  public function testShouldAttributesBeAddedToBlock(): void {
    $sut = $this->getSut(TRUE);
    // Test with invalid block.
    $this->assertFalse($sut->shouldAttributesBeAddedToBlock('invalid block'));
    // Test with correct block.
    $this->assertTrue($sut->shouldAttributesBeAddedToBlock('menu_block_current_language:header-top-navigation'));
  }

  /**
   * Tests ::checkIfBlockHasFallbackContent().
   */
  public function testCheckIfBlockHasFallbackContent(): void {
    $sut = $this->getSut(TRUE);
    // Test empty content.
    $this->assertFalse($sut->checkIfBlockHasFallbackContent(['content' => []]));
    // Test empty menu name and items.
    $this->assertFalse($sut->checkIfBlockHasFallbackContent([
      'content' => [
        '#menu_name' => 'header-top-navigation',
      ],
    ]));
    $this->assertFalse($sut->checkIfBlockHasFallbackContent([
      'content' => [
        '#items' => [1, 2],
      ],
    ]));

    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(TRUE);
    $url->getRouteName()->willReturn('<nolink>');
    // Test valid fallback content.
    $this->assertTrue($sut->checkIfBlockHasFallbackContent([
      'content' => [
        '#menu_name' => 'header-top-navigation',
        '#items' => [
          ['url' => $url->reveal()],
        ],
      ],
    ]));
  }

  /**
   * Tests ::shouldMenuTreeBeReplaced().
   */
  public function testShouldMenuTreeBeReplaced(): void {
    $sut = $this->getSut(TRUE);
    // Test with invalid menu name.
    $this->assertFalse($sut->shouldMenuTreeBeReplaced('invalid', []));
    // Test with empty items.
    $this->assertFalse($sut->shouldMenuTreeBeReplaced('header-top-navigation', []));
    // Test with more than one link.
    $this->assertFalse($sut->shouldMenuTreeBeReplaced('header-top-navigation', [1, 2, 3]));
    // Test with invalid URL.
    $this->assertFalse($sut->shouldMenuTreeBeReplaced('header-top-navigation', [1]));
    $this->assertFalse($sut->shouldMenuTreeBeReplaced('header-top-navigation', [
      ['url' => NULL],
    ]));

    // Test with non-routed link.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(FALSE);
    $this->assertFalse($sut->checkIfBlockHasFallbackContent([
      'content' => [
        '#menu_name' => 'header-top-navigation',
        '#items' => [
          ['url' => $url->reveal()],
        ],
      ],
    ]));
  }

}
