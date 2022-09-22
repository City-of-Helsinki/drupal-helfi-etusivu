<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Entity\Form;

use Drupal\Core\Entity\EntityListBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an overview form for global menu entities.
 */
final class GlobalMenuOverviewForm extends FormBase {

  /**
   * The entity storage.
   *
   * @var \Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage
   */
  private GlobalMenuStorage $storage;

  /**
   * The entity list builder.
   *
   * @var \Drupal\Core\Entity\EntityListBuilderInterface
   */
  private EntityListBuilderInterface $listBuilder;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->storage = $entityTypeManager->getStorage('global_menu');
    $this->listBuilder = $entityTypeManager->getListBuilder('global_menu');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new self(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'global_menus_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) : array {
    $form['entities'] = [
      '#type' => 'table',
      '#empty' => $this->t('No entities available.'),
      '#header' => [
        'entity' => $this->t('Name'),
        'status' => $this->t('Published'),
        'operations' => $this->t('Operations'),
        'weight' => NULL,
      ],
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'group' => 'entity-weight',
          'relationship' => 'sibling',
        ],
      ],
    ];

    foreach ($this->storage->loadMultipleSorted() as $delta => $entity) {
      $form['entities'][$delta] = [
        '#item' => $entity,
        '#attributes' => [
          'class' => ['draggable'],
        ],
        'entity' => [
          '#type' => 'link',
          '#title' => $entity->label() ?: $entity->id(),
          '#url' => $entity->toUrl('edit-form'),
        ],
        'status' => [
          '#type' => 'checkbox',
          '#default_value' => $entity->isPublished(),
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => $this->listBuilder->getOperations($entity),
        ],
        'weight' => [
          '#type' => 'weight',
          '#default_value' => $entity->getWeight(),
          '#attributes' => ['class' => ['entity-weight']],
        ],
      ];
    }

    $form['actions'] = ['#type' => 'actions', '#tree' => FALSE];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : void {
    foreach ($form_state->getValue('entities') as $id => $values) {
      if (!isset($form['entities'][$id]['#item'])) {
        continue;
      }
      /** @var \Drupal\helfi_global_navigation\Entity\GlobalMenu $entity */
      $entity = $form['entities'][$id]['#item'];

      ['weight' => $weight, 'status' => $status] = $values;
      $status ? $entity->setPublished() : $entity->setUnpublished();

      $entity->setWeight((int) $weight)
        ->save();
    }
  }

}
