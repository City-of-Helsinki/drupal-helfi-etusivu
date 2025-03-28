<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_annif\Traits;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\helfi_annif\Entity\SuggestedTopics;
use Drupal\helfi_annif\RecommendableInterface;
use Drupal\helfi_annif\TextConverter\TextConverterInterface;
use Drupal\helfi_annif\TextConverter\TextConverterManager;
use Drupal\helfi_annif\TopicsManager;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Provides shared functionality for tests.
 */
trait AnnifApiTestTrait {

  use ProphecyTrait;
  use ApiTestTrait {
    getFixture as private apiGetFixture;
  }

  /**
   * Gets the fixture data.
   *
   * @param string $name
   *   The fixture name.
   *
   * @return string
   *   The fixture.
   */
  protected function getFixture(string $name): string {
    $file = sprintf("%s/../../fixtures/%s", __DIR__, $name);

    if (!file_exists($file)) {
      throw new \InvalidArgumentException(sprintf('Fixture %s not found', $name));
    }

    return file_get_contents($file);
  }

  /**
   * Mocks entity.
   *
   * @param string $langcode
   *   Entity langcode. The API supports 'fi','sv','en'.
   * @param bool|null $hasKeywords
   *   Value for keyword field ->isEmpty(), NULL for ->hasField() = FALSE.
   * @param bool|null $shouldSave
   *   Bool if $entity->save() should be called, NULL for no opinion.
   */
  protected function mockEntity(string $langcode = 'fi', bool|NULL $hasKeywords = FALSE, bool|NULL $shouldSave = NULL): RecommendableInterface {
    $language = $this->prophesize(LanguageInterface::class);
    $language
      ->getId()
      ->willReturn($langcode);

    $entity = $this->prophesize(RecommendableInterface::class);
    $entity
      ->language()
      ->willReturn($language->reveal());

    $entity->getEntityTypeId()->willReturn('test_entity');
    $entity->bundle()->willReturn('test_entity');
    $entity->id()->willReturn($this->randomString());

    $entity
      ->hasField(Argument::exact(TopicsManager::TOPICS_FIELD))
      ->willReturn($hasKeywords !== NULL);
    $entity->hasKeywords()->willReturn($hasKeywords ?? FALSE);

    $topicsEntity = $this->prophesize(SuggestedTopics::class);

    $field = $this->prophesize(EntityReferenceFieldItemListInterface::class);
    $field->isEmpty()->willReturn(!$hasKeywords);
    $field->referencedEntities()->willReturn([$topicsEntity->reveal()]);

    $entity
      ->get(Argument::exact(TopicsManager::TOPICS_FIELD))
      ->willReturn($field->reveal());

    if (is_bool($shouldSave)) {
      if ($shouldSave) {
        $topicsEntity->set(Argument::any(), Argument::any())->shouldBeCalled();
        $topicsEntity->save()->shouldBeCalled();
      }
      else {
        $topicsEntity->save()->shouldNotBeCalled();
      }
    }

    return $entity->reveal();
  }

  /**
   * Gets text converter manager.
   */
  private function getTextConverterManager(?TextConverterInterface $textConverter = NULL): TextConverterManager {
    if (!$textConverter) {
      $textConverter = $this->prophesize(TextConverterInterface::class);
      $textConverter
        ->applies(Argument::any())
        ->willReturn(TRUE);

      $textConverter
        ->convert(Argument::any())
        ->willReturn('Test content');

      $textConverter = $textConverter->reveal();
    }

    $textConverterManager = new TextConverterManager();
    $textConverterManager->add($textConverter);

    return $textConverterManager;
  }

}
