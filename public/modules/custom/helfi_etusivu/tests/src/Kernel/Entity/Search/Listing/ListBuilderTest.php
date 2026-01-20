<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity\Search\Listing;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_etusivu\Entity\Search\Promotion;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\helfi_etusivu\Kernel\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the search promotion list builder.
 */
#[Group('helfi_etusivu')]
class ListBuilderTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
    'text',
    'helfi_etusivu',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('helfi_search_promotion');

    ConfigurableLanguage::createFromLangcode('fi')->save();
    ConfigurableLanguage::createFromLangcode('sv')->save();
  }

  /**
   * Tests the buildRow method.
   */
  public function testBuild(): void {

    $listBuilder = $this->container
      ->get('entity_type.manager')
      ->getListBuilder('helfi_search_promotion');

    $promotion = Promotion::create([
      'title' => 'Test Promotion 1',
      'description' => 'Test description 1',
      'link' => 'https://en.example.com',
      'keywords' => ['keyword1', 'keyword2'],
      'status' => TRUE,
    ]);
    $promotion->addTranslation('sv', array_merge($promotion->toArray(), [
      'link' => 'https://sv.example.com',
    ]));
    $promotion->save();

    $promotion = Promotion::create([
      'title' => 'Test Promotion 2',
      'description' => 'Test description (en) 2',
      'link' => 'https://en.example.com',
      'keywords' => ['keyword1', 'keyword2'],
      'status' => TRUE,
    ]);
    $promotion->addTranslation('fi', array_merge($promotion->toArray(), [
      'link' => 'https://fi.example.com',
    ]));
    $promotion->save();

    $table = $listBuilder->render();

    $this->assertCount(2, $table['table']['#rows']);
    foreach ($table['table']['#rows'] as $row) {
      $this->assertStringContainsString('https://en.example.com', $row['link']);
    }

    $languageManager = $this->container->get(LanguageManagerInterface::class);
    $this->assertInstanceOf(ConfigurableLanguageManagerInterface::class, $languageManager);

    // Override the current language.
    $languageManager->setCurrentLanguage($languageManager->getLanguage('fi'));

    $table = $listBuilder->render();

    $this->assertCount(2, $table['table']['#rows']);
    $this->assertNotEmpty(array_find($table['table']['#rows'], fn ($row) => str_contains($row['link'], 'https://fi.example.com')));
  }

}
