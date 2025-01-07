<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Linkit\Matcher;

use Drupal\Core\Url;
use Drupal\linkit\Plugin\Linkit\Matcher\EntityMatcher;
use Exception;

/**
 * Extends default EntityMatcher to add Helfi-specific configuration.
 *
 * @Matcher(
 *   id = "helfi_entity",
 *   label = @Translation("Helfi Entity"),
 *   target_entity = "node"
 * )
 */
class HelfiEntityMatcher extends EntityMatcher {

  /**
   * {@inheritdoc}
   */
  protected function findEntityIdByUrl($user_input)
  {
    $result = [];

    try {
      $params = Url::fromUserInput($user_input)->getRouteParameters();
      if (key($params) === $this->targetType) {
        $result = [end($params)];
      }
    }
    catch (Exception $e) {
      // Do nothing.
    }

    return $result;
  }
}
