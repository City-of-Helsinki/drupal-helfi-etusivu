<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\helfi_global_navigation\Plugin\Menu\ExternalMenuLink;
use function GuzzleHttp\json_decode;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use Psr\Log\LoggerInterface;

/**
 * Helper class for external menu tree actions.
 */
class ExternalMenuTreeFactory {

  /**
   * The JSON schema.
   *
   * @var object
   */
  protected object $schema;

  /**
   * The JSON validator.
   *
   * @var JsonSchema\Validator
   */
  protected Validator $validator;

  /**
   * Constructs a tree instance from supplied JSON.
   *
   * @param \JsonSchema\SchemaStorage $schemaStorage
   *   JSON Schema storage.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger channel.
   */
  public function __construct(
    protected SchemaStorage $schemaStorage,
    protected LoggerInterface $logger
  ) {
    $this->schema = json_decode(file_get_contents(__DIR__ . '/../assets/schema.json'));
    $this->schemaStorage->addSchema('file://schema', $this->schema);
    $this->validator = new Validator(new Factory($this->schemaStorage));
  }

  /**
   * Form and return a menu tree instance from json input.
   *
   * @param string $json
   *   The JSON string.
   * @param int $maxDepth
   *   Determies how deep of an array is returned.
   *
   * @return \Drupal\helfi_global_navigation\ExternalMenuTree
   *   The resulting menu tree instance.
   */
  public function fromJson(string $json, int $maxDepth):? ExternalMenuTree {
    $data = (array) json_decode($json);
    $isValid = $this->validate($data);

    if (!$isValid) {
      throw new \Exception('Invalid JSON input');
    }

    $tree = $this->transformItems($data, $maxDepth);

    if (!empty($tree)) {
      return new ExternalMenuTree($tree);
    }

    return NULL;
  }

  /**
   * Validates JSON against the schema.
   *
   * @param array $json
   *   The json string to validate.
   */
  protected function validate(array $json): bool {
    $this->validator->validate($json, $this->schema);

    if ($this->validator->isValid()) {
      return TRUE;
    }
    else {
      $errorString = '';
      foreach ($this->validator->getErrors() as $error) {
        $errorString .= sprintf('[%s] %s \n', $error['property'], $error['message']);
      }

      $this->logger->notice('Validation failed for external menu. Violations: \n' . $errorString);
      return FALSE;
    }
  }

  /**
   * Create menu link instances from json elements.
   *
   * @param array $items
   *   Provided JSON input.
   * @param int $maxDepth
   *   Determines how deep the function recurses.
   * @param string $name
   *   Menu name.
   * @param int $depth
   *   Defines how deep into recursion the function is already.
   *
   * @return array
   *   Resuliting array of menu links.
   */
  protected function transformItems(array $items, int $maxDepth, string $name = NULL, $depth = 0): array {
    $transformedItems = [];

    foreach ($items as $key => $item) {
      $menuName = $name ?? $item->name;

      $linkDefinition = [
        'menu_name' => $menuName,
        'options' => [],
        'title' => $item->name,
      ];

      if (isset($item->description)) {
        $linkDefinition['description'] = $item->description;
      }

      if (isset($item->weight)) {
        $linkDefinition['weight'] = $item->weight;
      }

      $transformedItem = [
        'attributes' => new Attribute(),
        'title' => $item->name,
        'original_link' => new ExternalMenuLink([], $item->id, $linkDefinition),
        'url' => Url::fromUri($item->url),
      ];

      if (isset($item->menu_tree) && $depth <= $maxDepth) {
        $transformedItem['below'] = $this->transformItems($item->menu_tree, $maxDepth, $menuName, $depth + 1);
      }
      else {
        $transformedItem['below'] = [];
      }

      $transformedItems[] = $transformedItem;
    }

    usort($transformedItems, function ($a, $b) {
      return $a['original_link']->getWeight() - $b['original_link']->getWeight();
    });

    return $transformedItems;
  }

}
