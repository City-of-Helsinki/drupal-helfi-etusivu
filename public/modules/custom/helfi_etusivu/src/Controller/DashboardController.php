<?php

declare(strict_types = 1);

namespace Drupal\helfi_etusivu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\helfi_api_base\Environment\EnvironmentResolverInterface;
use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
   */
  public function __construct(
    private EnvironmentResolverInterface $environmentResolver,
    private ClientInterface $client,
    private RendererInterface $renderer,
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
    );
  }

  /**
   * Lists available projects.
   *
   * @return array
   *   The response.
   */
  public function index() : array {
    $build = [
      'links' => [
        '#type' => 'table',
        '#header' => [$this->t('Project')],
      ],
    ];

    foreach ($this->environmentResolver->getProjects() as $name => $project) {
      $build['links'][$name]['name'] = [
        '#markup' => $name,
      ];

      foreach (EnvironmentEnum::cases() as $case) {
        if (!$project->hasEnvironment($case->value)) {
          continue;
        }
        $environment = $project->getEnvironment($case->value);

        $build['links'][$name][$case->value] = [
          '#type' => 'link',
          '#title' => $this->t('Link to %env environment', ['%env' => $case->value]),
          '#url' => Url::fromUri($environment->getUrl('en')),
        ];
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
   * @return \Symfony\Component\HttpFoundation\Response
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
      $instances = $this
        ->config('helfi_etusivu.debug_api_accounts')
        ->get('instances') ?? [];

      $account = array_filter(
        $instances,
        fn (array $instance) => $instance['name'] === $project && $instance['environment'] === $environment
      );

      if (!$account = reset($account)) {
        throw new BadRequestHttpException('No credentials found for given project.');
      }
      $projectEnv = $this->environmentResolver->getProject($query['project'])
        ->getEnvironment($query['environment']);
      $response = $this->client->request(
        'GET',
        $projectEnv->getInternalAddress($language) . '/api/v1/debug',
        [
          'headers' => [
            'Authorization' => 'Basic ' . $account['token'],
          ],
        ],
      );
      $items = json_decode($response->getBody()->getContents(), TRUE);

      $content = '';
      foreach ($items as $id => $item) {
        // Global menu status plugin is Frontpage specific, so we can safely
        // ignore it.
        if ($id === 'helfi_globalmenu') {
          continue;
        }
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
      throw new BadRequestHttpException($e->getMessage(), $e);
    }
  }

  /**
   * Lists debug information for all projects.
   *
   * @return array
   *   The render array.
   */
  public function health() : array {
    // We only care about health of test/stage/prod.
    $environments = [
      EnvironmentEnum::Test,
      EnvironmentEnum::Stage,
      EnvironmentEnum::Prod,
    ];

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

      foreach ($environments as $env) {
        if (!$project->hasEnvironment($env->value)) {
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
