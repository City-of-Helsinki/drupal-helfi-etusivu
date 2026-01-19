<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search\Listing;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_etusivu\Entity\Search\Promotion;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder for search promotions.
 */
final class ListBuilder extends EntityListBuilder {

  /**
   * The language manager.
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): self {
    $instance = parent::createInstance($container, $entity_type);
    $instance->languageManager = $container->get(LanguageManagerInterface::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Title');
    $header['link'] = $this->t('Link');
    $header['keywords'] = $this->t('Keywords', options: ['context' => 'Helfi search']);
    $header['published'] = $this->t('Published');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    assert($entity instanceof Promotion);
    $row['title'] = $entity->toLink($entity->label(), 'edit-form');
    $row['link'] = $entity->getUrl()->toString();
    $row['keywords'] = Unicode::truncate(implode(', ', $entity->getKeywords()), 32, add_ellipsis: TRUE);
    $row['published'] = $entity->isPublished() ? $this->t('Published') : $this->t('Unpublished');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_ids = $this->getEntityIds();
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    $entities = $this->storage->loadMultiple($entity_ids);
    foreach ($entities as $entity_id => $entity) {
      assert($entity instanceof TranslatableInterface);
      if ($entity->hasTranslation($current_language)) {
        $entities[$entity_id] = $entity->getTranslation($current_language);
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();
    $build['#cache']['contexts'][] = 'languages:language_interface';
    return $build;
  }

}
