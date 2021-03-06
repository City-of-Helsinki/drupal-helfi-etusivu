<?php

declare(strict_types = 1);

namespace Drupal\helfi_global_navigation\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_api_base\Environment\Environment;
use Drupal\helfi_api_base\Environment\EnvironmentResolver;
use Drupal\helfi_api_base\Environment\Project;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service class for global navigation related functions.
 */
class GlobalNavigationService implements ContainerInjectionInterface {

  /**
   * Current project.
   *
   * @var array
   */
  protected $currentProject;

  /**
   * Current environment (local/test/prod).
   *
   * @var string
   */
  protected $env;

  /**
   * Construct an instance.
   *
   * @param Drupal\Core\Cache\CacheBackendInterface $dataCache
   *   The data cache.
   * @param GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param Drupal\helfi_api_base\Environment\EnvironmentResolver $environmentResolver
   *   EnvironmentResolver helper class.
   * @param Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger channel.
   * @param Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack.
   */
  public function __construct(
    protected CacheBackendInterface $dataCache,
    protected ClientInterface $httpClient,
    protected EnvironmentResolver $environmentResolver,
    protected LanguageManagerInterface $languageManager,
    protected LoggerInterface $logger,
    protected RequestStack $requestStack
  ) {
    $this->env = getenv('APP_ENV');
    $this->currentProject = $this->initializeProject();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.default'),
      $container->get('http_client'),
      $container->get('helfi_api_base.environment_resolver'),
      $container->get('language_manager'),
      $container->get('logger.channel.helfi_global_navigation'),
      $container->get('request_stack')
    );
  }

  /**
   * Check if environment is frontpage.
   *
   * @param array $environment
   *   The environment to check.
   */
  public function isFrontPage(array $environment): bool {
    return $this->currentProject[$this->env]->getDomain() === $environment->getDomain();
  }

  /**
   * Return current env.
   *
   * @return string
   *   The env.
   */
  public function getEnv(): string {
    return $this->env;
  }

  /**
   * Return the current project.
   *
   * @return array
   *   The project
   */
  public function getCurrentProject(): array {
    return $this->currentProject;
  }

  /**
   * Check if current instance is frontpage.
   */
  public function inFrontPage(): bool {
    return $this->getCurrentProject()['project'][$this->env]->getDomain() === $this->getFrontPage()->getDomain();
  }

  /**
   * Makes a request based on parameters and returns the response.
   *
   * @param string $project
   *   The project or instance to which the request is made.
   * @param string $method
   *   Request method.
   * @param string $endpoint
   *   The endpoint in the instance.
   * @param array $options
   *   Body for requests.
   *
   * @return string
   *   The response body.
   */
  public function makeRequest(string $project, string $method, string $endpoint, array $options = []): string {
    $url = $this->getProjectUrl($project) . $endpoint;

    // Disable SSL verification in local environment.
    if ($this->env === 'local') {
      $options['verify'] = FALSE;
    }

    if ($method === 'GET') {
      return $this->getContent($url, $options);
    }

    try {
      $response = $this->httpClient->request($method, $url, $options);
      $content = (string) $response->getBody();

      return $content;
    }
    catch (\throwable $e) {
      $this->logger->error('Request failed with error: ' . $e->getMessage());

      return [];
    }
  }

  /**
   * Make a get request and cache results.
   *
   * @param string $url
   *   The url for the request.
   * @param array $options
   *   Possible options for the request.
   *
   * @return string
   *   The response body.
   */
  public function getContent(string $url, array $options = []): string {
    if ($data = $this->getFromCache($url)) {
      return $data;
    }

    try {
      $response = $this->httpClient->request('GET', $url, $options);
      $content = (string) $response->getBody();
      $this->setCache($url, $content);

      return $content;
    }
    catch (\throwable $e) {
      $this->logger->error('Request failed with error: ' . $e->getMessage());

      return $response;
    }
  }

  /**
   * Gets the cache key for given id.
   *
   * @param string $id
   *   The id.
   *
   * @return string
   *   The cache key.
   */
  protected function getCacheKey(string $id): string {
    $id = preg_replace('/[^a-z0-9_]+/s', '_', $id);

    return sprintf('global-navigation-%s', $id);
  }

  /**
   * Gets cached data for given id.
   *
   * @param string $id
   *   The id.
   *
   * @return string|null
   *   The cached data or null.
   */
  protected function getFromCache(string $id):? string {
    $key = $this->getCacheKey($id);

    if (isset($this->data[$key])) {
      return $this->data[$key];
    }

    if ($data = $this->dataCache->get($key)) {
      return $data->data;
    }
    return NULL;
  }

  /**
   * Sets the cache.
   *
   * @param string $id
   *   The id.
   * @param mixed $data
   *   The data.
   */
  protected function setCache(string $id, $data): void {
    $key = $this->getCacheKey($id);
    $this->dataCache->set($key, $data, $this->getCacheMaxAge(), []);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() : int {
    return time() + 60 * 60;
  }

  /**
   * Return frontpage project.
   *
   * @return Drupal\helfi_api_base\Environment\Environment
   *   The frontpage project.
   */
  protected function getFrontPage(): Environment {
    return $this->environmentResolver->getEnvironment(Project::ETUSIVU, $this->env);
  }

  /**
   * Return project's environment-speficif URL with correct language parameter.
   *
   * @param string $id
   *   Project id.
   *
   * @return string
   *   The URL.
   */
  protected function getProjectUrl($id) {
    $currentLanguage = $this->languageManager->getCurrentLanguage()->getId();
    return $this->environmentResolver->getEnvironment($id, $this->env)->getUrl($currentLanguage);
  }

  /**
   * Determine current project.
   *
   * @return array|null
   *   The resulting environment or null.
   */
  protected function initializeProject(): array|NULL {
    $projects = $this->environmentResolver->getProjects();
    $currentHost = $this->requestStack->getCurrentRequest()->getHost();
    foreach ($projects as $key => $project) {
      if ($currentHost === $project[$this->env]->getDomain()) {
        return [
          'id' => $key,
          'project' => $project,
          'url' => $this->getProjectUrl($key),
        ];
      }
    }

    return NULL;
  }

}
