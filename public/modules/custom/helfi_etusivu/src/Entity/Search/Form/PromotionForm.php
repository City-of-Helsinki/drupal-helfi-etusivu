<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the promotion edit forms.
 */
final class PromotionForm extends ContentEntityForm {

  /**
   * Date formatter.
   */
  private DateFormatterInterface $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get(DateFormatterInterface::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   *
   * @phpstan-return array<string, mixed>
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\helfi_etusivu\Entity\Search\Promotion $entity*/
    $entity = $this->entity;

    // Promotion is not revisionable, so ContentEntityForm does not create the
    // 'advanced' vertical-tabs group. Add it here so scheduler fields (and the
    // Gin sidebar layout) have a group to attach to.
    if (!isset($form['advanced'])) {
      $form['advanced'] = [
        '#type' => 'vertical_tabs',
        '#weight' => 99,
      ];
    }

    $form['advanced']['#attributes']['class'][] = 'entity-meta';

    $form['meta'] = [
      '#type' => 'details',
      '#group' => 'advanced',
      '#weight' => -10,
      '#title' => $this->t('Status'),
      '#attributes' => ['class' => ['entity-meta__header']],
      '#tree' => TRUE,
    ];
    $form['meta']['published'] = [
      '#type' => 'item',
      '#markup' => $entity->isPublished() ? $this->t('Published') : $this->t('Not published'),
      '#access' => !$entity->isNew(),
      '#wrapper_attributes' => ['class' => ['entity-meta__title']],
    ];
    $form['meta']['changed'] = [
      '#type' => 'item',
      '#title' => $this->t('Last saved'),
      '#markup' => !$entity->isNew() ? $this->dateFormatter->format($entity->getChangedTime(), 'short') : $this->t('Not saved yet'),
      '#wrapper_attributes' => ['class' => ['entity-meta__last-saved']],
    ];
    $form['meta']['last_checked'] = [
      '#type' => 'item',
      '#title' => $this->t('Last link check', options: ['context' => 'Helfi search']),
      '#markup' => $entity->getLastChecked() > 0 ? $this->dateFormatter->format($entity->getLastChecked(), 'long') : $this->t('Never'),
    ];
    if ($entity->getFailedCheckCount() > 0) {
      $form['meta']['failed_check_count'] = [
        '#type' => 'item',
        '#title' => $this->t('Failed link checks', options: ['context' => 'Helfi search']),
        '#markup' => (string) $entity->getFailedCheckCount(),
      ];
    }
    $form['meta']['author'] = [
      '#type' => 'item',
      '#title' => $this->t('Author'),
      '#markup' => $entity->getOwner()->getDisplayName(),
      '#wrapper_attributes' => ['class' => ['entity-meta__author']],
    ];

    // Move the published status into the footer group, like NodeForm. On Gin
    // content forms this becomes the "Published" toggle in the sticky top bar.
    if (isset($form['status'])) {
      $form['status']['#group'] = 'footer';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $saved = parent::save($form, $form_state);

    $options = [
      '@type' => $this->getEntity()->getEntityType()->getLabel(),
      '%label' => $this->getEntity()->toLink()->toString(),
    ];

    $this
      ->messenger()
      ->addStatus($saved === SAVED_NEW
        ? $this->t('@type %label has been created.', $options)
        : $this->t('@type %label has been updated.', $options),
      );

    // Redirect the user to the overview page.
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));

    return $saved;
  }

}
