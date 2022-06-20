<?php

namespace Drupal\helfi_global_navigation\Plugin\Block;

use Drupal\helfi_global_navigation\ExternalMenuBlockInterface;

/**
 * For testing purposes.
 *
 * @Block(
 *   id = "test_block",
 *   admin_label = @Translation("Test"),
 *   category = @Translation("Testing")
 * )
 */
class TestBlock extends ExternalMenuBlockBase implements ExternalMenuBlockInterface {

  /**
   * {@inheritdoc}
   */
  public function getData(): string {
    $path = __DIR__ . '/../../../assets/test.json';

    return file_get_contents(__DIR__ . '/../../../assets/test.json');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->buildFromJson($this->getData());
  }

  /**
   * {@inheritdoc}
   */
  public function maxDepth(): int {
    return 2;
  }

}
