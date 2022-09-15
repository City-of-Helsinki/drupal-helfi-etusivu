<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_global_navigation\Kernel;

use Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage;

/**
 * Tests the Global menu rest resource.
 *
 * @coversDefaultClass \Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage
 * @group helfi_global_navigation
 */
class GlobalMenuStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_language_negotiator_test',
  ];

  /**
   * Gets the entity storage class.
   *
   * @return \Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage
   *   The entity storage class.
   */
  private function getStorage() : GlobalMenuStorage {
    return $this->entityTypeManager->getStorage('global_menu');
  }

  /**
   * Tests optional conditions.
   */
  public function testMultipleSortedConditions() : void {
    for ($i = 1; $i <= 10; $i++) {
      $entity = $this->getStorage()->createById('item_' . $i);
      $entity->setWeight($i)
        ->save();
    }

    $this->assertCount(1, $this->getStorage()->loadMultipleSorted(
      ['weight' => 1],
    ));
  }

  /**
   * Make sure the entity language matches expected language.
   *
   * @param array $entities
   *   The entities to check.
   * @param string $expectedLangcode
   *   The expected language code.
   */
  private function assertEntityLanguage(array $entities, string $expectedLangcode) : void {
    foreach ($entities as $entity) {
      $this->assertEquals($expectedLangcode, $entity->language()->getId());
    }
  }

  /**
   * Tests force current language condition.
   *
   * @dataProvider currentLanguageConditionData
   */
  public function testCurrentLanguageCondition(string $langcode) : void {
    $this->setOverrideLanguageCode($langcode);

    $liikenne = $this->getStorage()->createById('liikenne')
      ->setLabel('Liikenne en');
    $liikenne->save();
    $sote = $this->getStorage()->createById('sote')
      ->setLabel('Sote en');
    $sote->save();

    $liikenne->addTranslation($langcode)
      ->setLabel('Liikenne ' . $langcode)
      ->save();
    $sote->addTranslation($langcode)
      ->setLabel('Sote ' . $langcode)
      ->save();

    $entities = $this->getStorage()->loadMultipleSorted(forceCurrentLanguage: FALSE);
    $this->assertEntityLanguage($entities, 'en');

    $entities = $this->getStorage()->loadMultipleSorted();
    $this->assertEntityLanguage($entities, $langcode);
  }

  /**
   * Data provider for testCurrentLanguageCondition().
   *
   * @return \string[][]
   *   The data.
   */
  public function currentLanguageConditionData() : array {
    return [
      ['fi'],
      ['sv'],
    ];
  }

  /**
   * Asserts that entities are loaded in given order.
   *
   * @param \Drupal\helfi_global_navigation\Entity\GlobalMenu[] $entities
   *   The entities.
   * @param array $expectedOrder
   *   The expected entity order.
   */
  private function assertEntityOrder(array $entities, array $expectedOrder) : void {
    $i = 0;
    foreach ($entities as $entity) {
      $this->assertTrue($entity->id() === $expectedOrder[$i++]);
    }
  }

  /**
   * Tests that items are sorted in correct order by default.
   */
  public function testLoadMultipleSorted() : void {
    $liikenne = $this->getStorage()->createById('liikenne')
      ->setWeight(1);
    $liikenne->save();
    $sote = $this->getStorage()->createById('sote')
      ->setWeight(2);
    $sote->save();

    $entities = $this->getStorage()->loadMultipleSorted();
    $this->assertEntityOrder($entities, [
      'liikenne',
      'sote',
    ]);
    // Sort liikenne below sote and make sure items are loaded in correct order.
    $liikenne->setWeight(3)->save();
    $entities = $this->getStorage()->loadMultipleSorted();
    $this->assertEntityOrder($entities, [
      'sote',
      'liikenne',
    ]);
  }

}
