<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Plugin\Block;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Annotation\ContextDefinition;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_annif\RecommendationManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides 'AI powered recommendations'.
 */
#[Block(
  id: "helfi_recommendations",
  admin_label: new TranslatableMarkup("AI powered recommendations"),
  context_definitions: [
    'node' => new \Drupal\Core\Plugin\Context\ContextDefinition('entity:node', new TranslatableMarkup('Node'), FALSE)
  ]
)]
class RecommendationsBlock extends BlockBase implements ContainerFactoryPluginInterface, LoggerAwareInterface, ContextAwarePluginInterface {

  use LoggerAwareTrait;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly ContextRepositoryInterface $contextRepository,
    private readonly RecommendationManager $recommendationManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : static {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('context.repository'),
      $container->get('helfi_annif.recommendation_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() : array {
    $x = 1;
    try {
      $node = $this->getContextValue('node');
      $results = $this->recommendationManager->getRecommendations($node);
    }
    catch (ContextException $exception) {
      $x = 1;
      // $this->logger->error($exception->getMessage());
      return [];
    }



    // @todo UHF-9962.
    return [
      '#markup' => $this->t('Hello, World!'),
    ];
  }

}
