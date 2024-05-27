<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Plugin\Block;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
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
    'node' => new ContextDefinition('entity:node', new TranslatableMarkup('Node'), FALSE),
  ]
)]
class RecommendationsBlock extends BlockBase implements ContainerFactoryPluginInterface, ContextAwarePluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly ContextRepositoryInterface $contextRepository,
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
      $container->get('context.repository'),
      $container->get('helfi_annif.recommendation_manager'),
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
      $this->logger->error($exception->getMessage());
      return [];
    }

    // @todo #UHF-9964 Lisätään suosittelulohkon piilotustoiminto.
    $response = [
      '#theme' => 'recommendations_block',
      '#title' => $this->t('You might be interested in'),
      '#cache' => ['tags' => ["{$node->getEntityTypeId()}:{$node->id()}"]],
    ];

    $recommendations = $this->recommendationManager->getRecommendations($node);
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

}
