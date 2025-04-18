<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\TextConverter;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Coverts entity to text by rendering it and then stripping html tags.
 */
final class RenderTextConverter implements TextConverterInterface {

  const TEXT_CONVERTER_VIEW_MODE = 'text_converter';

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $displayRepository
   *   The entity display repository.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityDisplayRepositoryInterface $displayRepository,
    private readonly RendererInterface $renderer,
    private readonly ModuleHandlerInterface $moduleHandler,
  ) {
  }

  /**
   * {@inheritDoc}
   */
  public function applies(EntityInterface $entity): bool {
    // This converter matches entities that have
    // text_converter display enabled.
    $viewModes = $this
      ->displayRepository
      ->getViewModeOptionsByBundle($entity->getEntityTypeId(), $entity->bundle());

    return array_key_exists(self::TEXT_CONVERTER_VIEW_MODE, $viewModes);
  }

  /**
   * {@inheritDoc}
   */
  public function convert(EntityInterface $entity): string {
    $builder = $this
      ->entityTypeManager
      ->getViewBuilder($entity->getEntityTypeId());

    $view = $builder->view($entity, self::TEXT_CONVERTER_VIEW_MODE, $entity->language()->getId());
    $markup = $this->renderer
      ->renderInIsolation($view);

    $document = new Document($markup);

    // Allow markup postprocessing.
    $this->moduleHandler->alter(
      ['text_conversion', $entity->getEntityTypeId() . '_text_conversion'],
      $document,
      $entity
    );

    // Strip HTML tags, entities and excessive newlines.
    return trim(preg_replace("/\n\s*/u", "\n", html_entity_decode(strip_tags((string) $document))));
  }

}
