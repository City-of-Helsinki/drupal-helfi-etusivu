<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase as CoreEntityKernelTestBase;

/**
 * Base class for entity kernel tests.
 */
abstract class EntityKernelTestBase extends CoreEntityKernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'big_pipe',
  ];

}
