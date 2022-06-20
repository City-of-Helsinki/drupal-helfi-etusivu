<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation;

use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use function GuzzleHttp\json_decode;
use JsonSchema\Validator;
use Psr\Log\LoggerInterface;

/**
 * Helper class for external menu tree actions.
 */
class ExternalMenuTreeFactory {

  /**
   * The JSON schema.
   *
   * @var string
   */
  protected string $schema;

  /**
   * Constructs a tree instance from supplied JSON.
   *
   * @param \JsonSchema\Validator $validator
   *   JSON validator.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger channel.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   Menu link plugin manager.
   */
  public function __construct(
    protected Validator $validator,
    protected LoggerInterface $logger,
    protected MenuLinkManagerInterface $menuLinkManager
  ) {
    $this->schema = file_get_contents(__DIR__ . '/../assets/schema.json');
  }

  /**
   * Form and return a menu tree instance from json input.
   *
   * @param string $json
   *   The JSON string.
   *
   * @return \Drupal\helfi_global_navigation\ExternalMenuTree
   *   The resulting menu tree instance.
   */
  public function fromJson($json): ExternalMenuTree {
    $isValid = $this->validate($json);

    if (!$isValid) {
      throw new \Exception('Invalid JSON input');
    }

    $tree = $this->transformItems(json_decode($json));

    if (!empty($tree)) {
      return new ExternalMenuTree($tree);
    }

    return NULL;
  }

  /**
   * Validates JSON against the schema.
   *
   * @param string $json
   *   The json string to validate.
   */
  protected function validate(string $json): bool {
    $this->validator->validate($json, $this->schema);

    if ($this->validator->isValid()) {
      return TRUE;
    }
    else {
      $errorString = '';
      foreach ($this->validator->getErrors() as $error) {
        $errorString += sprintf('[%s] %s \n', $error['property'], $error['message']);
      }

      $logger->notice('Validation failed for external menu. Violations: \n' . $errorString);
      return FALSE;
    }
  }

  /**
   * Create menu link instances from json elements.
   *
   * @param array $items
   *   Provided JSON input.
   *
   * @return array
   *   Resuliting array of menu links.
   */
  protected function transformItems(array $items): array {
    $transformedItems = [];

    foreach ($items as $key => $item) {

      $transformedItems[] = [
        'attributes' => new Attribute(),
        'below' => [],
        'title' => $item->name,
        'original_link' => NULL,
        'url' => Url::fromUri($item->menu_tree->url),
      ];
    }

    return $transformedItems;
  }

}
