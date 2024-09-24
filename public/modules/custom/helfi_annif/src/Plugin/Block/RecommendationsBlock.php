<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Plugin\Block;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Error;
use Drupal\helfi_annif\RecommendableInterface;
use Drupal\helfi_annif\RecommendationManager;
use Drupal\helfi_annif\TopicsManager;
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
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountInterface $currentUser,
    private readonly LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    return new self($configuration, $plugin_id, $plugin_definition,
      $container->get(RecommendationManager::class),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('logger.channel.helfi_annif'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() : array {
    try {
      $node = $this->getContextValue('node');
    }
    catch (ContextException $exception) {
      Error::logException($this->logger, $exception);
      return [];
    }

    if (!$node instanceof RecommendableInterface || !$node->isBlockSetVisible()) {
      return [];
    }

    $response = [
      '#theme' => 'recommendations_block',
      '#title' => $this->t('You might be interested in', [], ['context' => 'Recommendations block title']),
    ];

    $recommendations = $this->getRecommendations($node);
    if (!$recommendations) {
      if ($this->currentUser->isAnonymous()) {
        return [];
      }

      $response['#no_results_message'] = $this->t('No recommended content has been created for this page yet.', [], ['context' => 'Annif']);
      return $response;
    }

    $view_builder = $this->entityTypeManager->getViewBuilder('node');

    // We want to render the recommendation results as nodes
    // so that all fields are correctly preprocessed.
    $nodes = [];
    foreach ($recommendations as $recommendation) {
      $nodes[] = $view_builder->view($recommendation, 'teaser');
    }

    $response['#rows'] = $nodes;

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(
      parent::getCacheContexts(),
      ['languages:language_content', 'user.roles:anonymous', 'url.path'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $node = $this->getContextValue('node');

    $topics = $node->get(TopicsManager::TOPICS_FIELD);
    assert($topics instanceof EntityReferenceFieldItemListInterface);

    $tags = [];
    foreach ($topics->referencedEntities() as $entity) {
      $tags[] = $entity->getCacheTags();
    }

    return Cache::mergeTags(parent::getCacheTags(), ...$tags);
  }

  /**
   * Get the recommendations for current content entity.
   *
   * @param \Drupal\helfi_annif\RecommendableInterface $node
   *   Content entity to find recommendations for.
   *
   * @return array
   *   Array of recommendations
   */
  private function getRecommendations(RecommendableInterface $node): array {
    try {
      $recommendations = $this->recommendationManager
        ->getRecommendations($node, 3, 'fi');
    }
    catch (\Exception $exception) {
      Error::logException($this->logger, $exception);
      return [];
    }
    return $recommendations;
  }

}
