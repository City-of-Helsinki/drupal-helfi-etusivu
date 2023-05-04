<?php

declare(strict_types = 1);

namespace Drupal\helfi_etusivu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\helfi_api_base\Environment\EnvironmentResolverInterface;
use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Vault\VaultManager;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Dashboard routes.
 */
final class DashboardController extends ControllerBase {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_api_base\Environment\EnvironmentResolverInterface $environmentResolver
   *   The environment resolver.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\helfi_api_base\Vault\VaultManager $vaultManager
   *   The vault manager.
   */
  public function __construct(
    private readonly EnvironmentResolverInterface $environmentResolver,
    private readonly ClientInterface $client,
    private readonly RendererInterface $renderer,
    private readonly VaultManager $vaultManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new self(
      $container->get('helfi_api_base.environment_resolver'),
      $container->get('http_client'),
      $container->get('renderer'),
      $container->get('helfi_api_base.vault_manager'),
    );
  }

  /**
   * Lists available projects.
   *
   * @return array
   *   The response.
   */
  public function index() : array {
    $serviceLinks = [
      'jira' => [
        'title' => $this->t('JIRA'),
        'url' => 'https://helsinkisolutionoffice.atlassian.net/jira/software/c/projects/UHF/boards/218',
      ],
      'documentation' => [
        'title' => $this->t('Documentation'),
        'url' => 'https://helsinkisolutionoffice.atlassian.net/wiki/spaces/HEL/pages/7710015550/Instanssit+ja+repositoryt',
      ],
    ];

    $build = [
      'links' => [
        '#type' => 'table',
        '#header' => [$this->t('Service links')],
      ],
    ];
    foreach ($serviceLinks as $id => $link) {
      $build['links'][$id] = [
        [
          '#markup' => $link['title'],
        ],
        [
          '#type' => 'link',
          '#title' => $link['url'],
          '#url' => Url::fromUri($link['url']),
        ],
      ];
    }

    foreach ($this->environmentResolver->getProjects() as $name => $project) {
      $build[$name] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $project->label(),
      ];

      $build[$name]['links'] = [
        '#type' => 'table',
        '#header' => [$this->t('Links')],
        'repository' => [
          [
            '#type' => 'link',
            '#title' => $this->t('Link to Git repository'),
            '#url' => Url::fromUri($project->getMetadata()->getRepositoryUrl()),
          ],
        ],
        'azure_devops' => [
          [
            '#type' => 'link',
            '#title' => $this->t('Link to Azure DevOps'),
            '#url' => Url::fromUri($project->getMetadata()->getAzureDevopsLink()),
          ],
        ],
      ];

      foreach (EnvironmentEnum::cases() as $case) {
        if (!$project->hasEnvironment($case->value)) {
          continue;
        }
        $environment = $project->getEnvironment($case->value);

        $build[$name]['links'][$case->value][] = [
          '#markup' => $case->label(),
        ];

        $build[$name]['links'][$case->value][] = [
          '#type' => 'link',
          '#title' => $this->t('Link to %env environment', ['%env' => $case->label()]),
          '#url' => Url::fromUri($environment->getUrl('en')),
        ];

        if ($metadata = $environment->getMetadata()) {
          $build[$name]['links'][$case->value][] = [
            '#type' => 'link',
            '#title' => $this->t('Link to %env OpenShift console', ['%env' => $case->label()]),
            '#url' => Url::fromUri($metadata->getOpenshiftConsoleLink()),
          ];
        }

      }
    }

    return $build;
  }

  /**
   * Proxies the JSON requests to corresponding environment.
   *
   * This is used to avoid CORS issues with external environments.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function apiProxy(Request $request) : JsonResponse {
    $query = $request->query->all();

    if (!isset($query['project'], $query['environment'])) {
      throw new BadRequestHttpException('Missing required "project", "environment" query parameter.');
    }
    ['project' => $project, 'environment' => $environment] = $query;

    $language = $this->languageManager()
      ->getCurrentLanguage(LanguageInterface::TYPE_URL)
      ->getId();

    try {
      $authorization = $this->vaultManager->get($project . '_' . $environment);

      if (!$authorization) {
        throw new BadRequestHttpException('No credentials found for given project.');
      }
      $projectEnv = $this->environmentResolver->getProject($query['project'])
        ->getEnvironment($query['environment']);
      $response = $this->client->request(
        'GET',
        $projectEnv->getInternalAddress($language) . '/api/v1/debug',
        [
          'headers' => [
            'Authorization' => 'Basic ' . $authorization->data(),
          ],
        ],
      );
      $items = json_decode($response->getBody()->getContents(), TRUE);

      $content = '';
      foreach ($items as $id => $item) {
        $build = [
          '#theme' => 'debug_item',
          '#id' => $id,
          '#label' => $item['label'],
          '#data' => $item['data'],
        ];
        $content .= $this->renderer->render($build);
      }

      return new JsonResponse(['status' => 'ok', 'content' => $content]);
    }
    catch (\InvalidArgumentException | RequestException $e) {
      throw match ($e->getResponse()->getStatusCode()) {
        403 => new AccessDeniedHttpException('Debug API returned a 403 (Access denied) error.', $e),
        404 => new NotFoundHttpException('Debug API returned a 404 error (Not found) error.', $e),
        default => new BadRequestHttpException(sprintf('Debug API endpoint returned an unknown error: %s', $e->getMessage()), $e),
      };
    }
  }

  /**
   * Lists debug information for all projects.
   *
   * @return array
   *   The render array.
   */
  public function status() : array {
    $build = [
      '#attached' => [
        'library' => ['helfi_etusivu/debug-status'],
      ],
    ];

    foreach ($this->environmentResolver->getProjects() as $name => $project) {
      $build[$name] = [
        '#type' => 'details',
        '#title' => $project->label(),
        '#open' => TRUE,
      ];

      foreach (EnvironmentEnum::cases() as $env) {
        if ($env === EnvironmentEnum::Local || !$project->hasEnvironment($env->value)) {
          continue;
        }
        $build[$name][$env->value] = [
          '#type' => 'details',
          '#title' => $env->label(),
          '#open' => TRUE,
          '#attributes' => [
            'data-environment' => $env->value,
            'data-project' => $name,
          ],
          '#markup' => $this->t('Fetching ...'),
        ];
      }
    }

    return $build;
  }

}
