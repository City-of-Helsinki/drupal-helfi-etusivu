<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_annif\RecommendationManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides 'AI powered recommendations'.
 */
#[Block(
  id: "helfi_recommendations",
  admin_label: new TranslatableMarkup("AI powered recommendations"),
  context_definitions: [
    'node' => new EntityContextDefinition(
      data_type: 'node',
      label: new TranslatableMarkup('Node'),
      required: TRUE,
    ),
  ]
)]
final class RecommendationsBlock extends BlockBase implements ContainerFactoryPluginInterface, ContextAwarePluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly RecommendationManager $recommendationManager,
    private readonly AccountInterface $currentUser,
    private readonly LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : static {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get(RecommendationManager::class),
      $container->get('current_user'),
      $container->get('logger.channel.helfi_annif'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() : array {
    $node = $this->getContextValue('node');

    // @todo #UHF-9964 Lisätään suosittelulohkon piilotustoiminto.
    $response = [
      '#theme' => 'recommendations_block',
      '#title' => $this->t('You might be interested in'),
    ];

    $recommendations = [];
    try {
      $recommendations = $this->recommendationManager
        ->getRecommendations($node, 3, 'fi');
    }
    catch (\Exception $exception) {
      $this->logger->error($exception->getMessage());
    }

    if (!$recommendations) {
      return $this->handleNoRecommendations($response);
    }

    $response['#rows'] = $recommendations;
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), ['languages:language_content']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(
      parent::getCacheTags(),
      $this->getContextValue('node')->getCacheTags(),
      $this->getAnnifKeywordCacheTags()
    );
  }

  /**
   * Return response when recommendations are not yet calculated.
   *
   * @param array $response
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  private function handleNoRecommendations(array $response): array {
    if ($this->currentUser->isAnonymous()) {
      return [];
    }

    $response['#no_results_message'] = $this->t('Calculating recommendations');
    return $response;
  }

  /**
   * Get list of cache tags for the block.
   *
   * @return array
   *   Array of cache tags for Annif-keywords related to this node.
   */
  private function getAnnifKeywordCacheTags(): array {
    $entity = $this->getContextValue('node');
    if (!$entity->hasField('field_annif_keywords') || $entity->get('field_annif_keywords')->isEmpty()) {
      return [];
    }

    $cacheTags = array_map(
      fn ($term) => $term->getCacheTags(),
      $entity->get('field_annif_keywords')->referencedEntities()
    );

    // Flatten array by merging the destructed arrays.
    return array_merge(...$cacheTags);
  }

}
