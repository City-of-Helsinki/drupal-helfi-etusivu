<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\search_api\processor;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\FileInterface;
use Drupal\helfi_platform_config\SearchAPI\Processor\MainImageProcessorProperties;
use Drupal\helfi_platform_config\SearchAPI\Processor\MainImageUrlProcessorBase;
use Drupal\node\NodeInterface;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Indexes main image uri in correct image style.
 */
#[SearchApiProcessor(
  id: 'main_image_url',
  label: new TranslatableMarkup('Main image'),
  description: new TranslatableMarkup('Indexes main image uri in correct image style'),
  stages: [
    'add_properties' => 0,
  ],
  locked: TRUE,
  hidden: TRUE,
)]
final class MainImageProcessor extends MainImageUrlProcessorBase {

  /**
   * The file url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->fileUrlGenerator = $container->get(FileUrlGeneratorInterface::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldProperties(): MainImageProcessorProperties {
    return new MainImageProcessorProperties(
      imageStyleField: 'main_image_url',
      entityField: 'field_main_image',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL): array {
    $properties = parent::getPropertyDefinitions($datasource);
    $properties['main_image'] = new ProcessorProperty([
      'label' => $this->t('Main image: original file'),
      'description' => $this->t('The original main image file'),
      'type' => 'string',
      'processor_id' => $this->getPluginId(),
    ]);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function isValid(NodeInterface $node): bool {
    return in_array($node->getType(), ['news_item', 'news_article']);
  }

  /**
   * {@inheritdoc}
   */
  protected function processFields(ItemInterface $item, FileInterface $file): void {
    parent::processFields($item, $file);

    $this->processOriginalFile($item, $file);
  }

  /**
   * Processes the original file field.
   *
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The item to process.
   * @param \Drupal\file\FileInterface $file
   *   The file to process.
   */
  private function processOriginalFile(ItemInterface $item, FileInterface $file): void {
    $fields = $this
      ->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), 'entity:node', 'main_image');

    $properties = [
      'url' => $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()),
      'size' => $file->getSize(),
      'mime' => $file->getMimeType(),
    ];

    foreach ($fields as $field) {
      $field->addValue(json_encode($properties));
    }
  }

}
