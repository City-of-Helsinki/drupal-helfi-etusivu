<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\LlmsTxt;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Servers /llms.txt path.
 */
final readonly class Controller implements ContainerInjectionInterface {

  use AutowireTrait;

  public function __construct(
    private ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Returns the llms.txt content.
   */
  public function __invoke(): CacheableResponse {
    $config = $this->configFactory->get('helfi_etusivu.llms_txt');

    $response = new CacheableResponse($config->get('content'), headers: [
      'Content-Type' => 'text/markdown; charset=utf-8',
    ]);

    $response->addCacheableDependency($config);

    return $response;
  }

}
