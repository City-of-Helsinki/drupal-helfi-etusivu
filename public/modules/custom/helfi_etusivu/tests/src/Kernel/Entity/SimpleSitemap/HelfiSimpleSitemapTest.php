<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity\SimpleSitemap;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\simple_sitemap\Entity\SimpleSitemap;

/**
 * Tests simple_sitemap Entity class overrides.
 *
 * @covers \Drupal\helfi_etusivu\Entity\SimpleSitemap\HelfiSimpleSitemap::toUrl
 * @group helfi_etusivu
 */
class HelfiSimpleSitemapTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'simple_sitemap',
    'big_pipe',
    'helfi_etusivu',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('simple_sitemap');
    $this->installEntitySchema('configurable_language');

    ConfigurableLanguage::createFromLangcode('fi')->save();
    ConfigurableLanguage::createFromLangcode('sv')->save();

    $config = $this->config('language.negotiation');
    $config->set('url.prefixes', ['fi' => 'fi', 'sv' => 'sv']);
    $config->set('url.source', LanguageNegotiationUrl::CONFIG_PATH_PREFIX);
    $config->save();

    $config = $this->config('language.types');
    $config->set('configurable', [LanguageInterface::TYPE_INTERFACE, LanguageInterface::TYPE_CONTENT]);
    $config->set('negotiation.language_content.enabled', [
      LanguageNegotiationUrl::METHOD_ID => 0,
    ]);
    $config->save();

    \Drupal::service('kernel')->rebuildContainer();
  }

  /**
   * Tests toUrl method.
   */
  public function testToUrl(): void {
    $simpleSitemap = SimpleSitemap::create();
    $url = $simpleSitemap->toUrl('canonical', ['base_url' => 'https://www.hel.fi']);
    $this->assertEquals('https://www.hel.fi/fi/sitemap.xml', $url->toString());
  }

}
