<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_annif\Entity\SuggestedTopics;
use Drupal\helfi_api_base\Environment\EnvironmentResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'suggested_topics_reference' field widget.
 *
 * @FieldWidget(
 *   id = "suggested_topics_reference",
 *   label = @Translation("Annif recommendations"),
 *   field_types = {"suggested_topics_reference"},
 * )
 */
#[FieldWidget(
  id: 'suggested_topics_reference',
  label: new TranslatableMarkup('Annif recommendations'),
  field_types: ['suggested_topics_reference'],
)]
final class SuggestedTopicsReferenceWidget extends WidgetBase {

  /**
   * The environment resolver.
   */
  private EnvironmentResolverInterface $environmentResolver;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new self($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings']);
    $instance->environmentResolver = $container->get(EnvironmentResolverInterface::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    assert($items instanceof EntityReferenceFieldItemListInterface);

    $hasTargetEntity = !empty($items[$delta]->target_id) && $items[$delta]->entity;

    $project = NULL;
    try {
      $project = $this->environmentResolver->getActiveProject()->getName();
    }
    catch (\Exception) {
    }

    /** @var \Drupal\helfi_annif\Entity\SuggestedTopics $entity */
    $entity = $hasTargetEntity ? $items[$delta]->entity : SuggestedTopics::create([
      'parent_type' => $items->getEntity()->getEntityTypeId(),
      'parent_instance' => $project,
    ]);

    $element['entity'] = [
      '#type' => 'value',
      '#value' => $entity,
    ];

    if ($hasTargetEntity) {
      $keywords = [];
      foreach ($entity->referencedEntities() as $keyword) {
        $keywords[] = $keyword->label();
      }

      $element['keywords'] = [
        '#theme' => 'item_list',
        '#items' => $keywords,
      ];

      $element['target_id'] = [
        '#type' => 'value',
        '#default_value' => $items[$delta]->target_id,
      ];
    }

    return $element;
  }

}
