<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search\Listing;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\helfi_etusivu\Entity\Search\Promotion;

/**
 * List builder for search promotions.
 */
final class ListBuilder extends EntityListBuilder {

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

    // Don't translate links with user interface language.
    // Keep the entity default langcode.
    $url = $entity
      ->getUrl()
      ->setOption('language', $entity->language())
      ->toString();

    $row['title'] = $entity->toLink($entity->label(), 'edit-form');
    $row['link'] = Unicode::truncate($url, 128, add_ellipsis: TRUE);
    $row['keywords'] = Unicode::truncate(implode(', ', $entity->getKeywords()), 32, add_ellipsis: TRUE);
    $row['published'] = $entity->isPublished() ? $this->t('Published') : $this->t('Unpublished');
    return $row + parent::buildRow($entity);
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
