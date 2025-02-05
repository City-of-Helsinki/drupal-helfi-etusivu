<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_alt_lang_fallback\Kernel;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\helfi_api_base\Traits\LanguageManagerTrait;

/**
 * Tests the Alt language fallbacks resource.
 *
 * @group helfi_alt_lang_fallback
 */
class AltLanguageFallbacksTest extends KernelTestBase {

  use LanguageManagerTrait;
  use ApiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_language_negotiator_test',
    'helfi_api_base',
    'helfi_alt_lang_fallback',
    'menu_block_current_language',
    'content_translation',
    'language',
    'menu_link_content',
    'locale',
    'block',
    'link',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['language', 'content_translation']);
    $this->installEntitySchema('menu_link_content');

    $this->installConfig(['content_translation']);
    $this->setupLanguages();
    // Install estonian.
    ConfigurableLanguage::createFromLangcode('et')->save();

    $this->config('language.negotiation')
      ->set('url.prefixes', ['en' => 'en', 'fi' => 'fi', 'sv' => 'sv', 'et' => 'et'])
      ->save();

    $this->enableTranslation(['menu_link_content']);

    Menu::create([
      'id' => 'headertopnavigation',
      'label' => 'Header top navigation',
    ])->save();

    \Drupal::service('kernel')->rebuildContainer();
  }

  /**
   * Creates a new menu link.
   *
   * @param string $title
   *   The title.
   * @param string $langcode
   *   The langcode.
   * @param string $menuName
   *   The menu name.
   * @param string $uri
   *   The uri.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   The menu link.
   */
  private function createMenuLink(string $title, string $langcode, string $menuName, string $uri = 'internal:/test-page') : MenuLinkContent {
    $link = MenuLinkContent::create([
      'menu_name' => $menuName,
      'title' => $title,
      'langcode' => $langcode,
      'link' => [
        'uri' => $uri,
      ],
    ]);
    $link->save();

    return $link;
  }

  /**
   * Renders the given menu block.
   *
   * @param string $menuId
   *   The menu to render.
   * @param string $langcode
   *   The langcode.
   *
   * @return array
   *   The menu tree.
   */
  private function renderMenuBlock(string $menuId, string $langcode) : array {
    $this->setOverrideLanguageCode($langcode);
    $blockManager = $this->container->get(BlockManagerInterface::class);
    $block = $blockManager->createInstance('menu_block_current_language:' . $menuId, [
      'region' => 'footer',
      'id' => 'machine_name',
      'theme' => 'stark',
      'level' => 1,
      'depth' => 0,
    ]);
    return $block->build();
  }

  /**
   * Tests replaceMenuTree().
   */
  public function testReplaceMenuTree() : void {
    $links = [
      [
        'title' => 'Test title en',
        'langcode' => 'en',
      ],
      [
        'title' => 'Test title fi',
        'langcode' => 'fi',
      ],
    ];

    foreach ($links as $item) {
      $this->createMenuLink($item['title'], $item['langcode'], 'headertopnavigation');
    }

    $build = $this->renderMenuBlock('headertopnavigation', 'en');
    // Make sure the default language filter works.
    $this->assertCount(1, $build['#items']);

    // Make sure nothing is rendered for alt language unless
    // there's a <nolink> link.
    $build = $this->renderMenuBlock('headertopnavigation', 'et');
    $this->assertArrayNotHasKey('#items', $build);

    // Make sure english link is rendered for alt language.
    $this->createMenuLink('Test title et', 'et', 'headertopnavigation', 'route:<nolink>');
    $build = $this->renderMenuBlock('headertopnavigation', 'et');
    $this->assertCount(1, $build['#items']);
    $key = key($build['#items']);
    $this->assertEquals('Test title et', $build['#items'][$key]['title']);
  }

}
