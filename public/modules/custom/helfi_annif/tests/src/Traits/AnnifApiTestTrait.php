<?php

namespace Drupal\Tests\helfi_annif\Traits;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\helfi_annif\TextConverter\TextConverterInterface;
use Drupal\helfi_annif\TextConverter\TextConverterManager;
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
   */
  protected function mockEntity(string $langcode = 'fi'): FieldableEntityInterface {
    $language = $this->prophesize(LanguageInterface::class);
    $language
      ->getId()
      ->willReturn($langcode);

    $entity = $this->prophesize(FieldableEntityInterface::class);
    $entity
      ->language()
      ->willReturn($language->reveal());

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
