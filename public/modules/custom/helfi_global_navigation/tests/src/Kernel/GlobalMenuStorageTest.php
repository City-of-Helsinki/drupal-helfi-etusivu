<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_global_navigation\Kernel;

use Drupal\helfi_global_navigation\Entity\GlobalMenu;

/**
 * Tests the Global menu rest resource.
 *
 * @group helfi_global_navigation
 */
class GlobalMenuStorageTest extends KernelTestBase {

  /**
   * Tests that items are sorted in correct order by default.
   */
  public function testLoadMultipleSorted() : void {
    $liikenne = GlobalMenu::createById('liikenne')
      ->setWeight(1);
    $liikenne->save();
    $sote = GlobalMenu::createById('sote')
      ->setWeight(2);
    $sote->save();

    /** @var \Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage $storage */
    $storage = $this->entityTypeManager->getStorage('global_menu');

    $entities = $storage->loadMultipleSorted();
    $this->assertTrue(current($entities)->id() === 'liikenne');
    $this->assertTrue(next($entities)->id() === 'sote');

    // Sort liikenne below sote and make sure items are loaded in correct order.
    $liikenne->setWeight(3)->save();

    $entities = $storage->loadMultipleSorted();
    $this->assertTrue(current($entities)->id() === 'sote');
    $this->assertTrue(next($entities)->id() === 'liikenne');
  }

}
