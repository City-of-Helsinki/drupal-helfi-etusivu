<?php

declare(strict_types = 1);

namespace Drupal\helfi_etusivu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\helfi_api_base\Environment\EnvironmentResolverInterface;
use Drupal\helfi_api_base\Environment\EnvMapping;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
   */
  public function __construct(
    private EnvironmentResolverInterface $environmentResolver,
    private ClientInterface $client,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new self(
      $container->get('helfi_api_base.environment_resolver'),
      $container->get('http_client'),
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

      foreach (EnvMapping::cases() as $case) {
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

  public function healthJson(Request $request) : JsonResponse {
    $project = $request->query->get('project');
    $environment = $request->query->get('environment');

    if (!$project || !$environment) {
      throw new NotFoundHttpException();
    }
    $projectEnv = $this->environmentResolver->getProject($project)
      ->getEnvironment($environment);
    $status = $this->client->request('GET', $projectEnv->getInternalAddress('en') . '/health');

    if ($status->getStatusCode() !== Response::HTTP_OK) {
      throw new BadRequestHttpException();
    }
    return new JsonResponse(['status' => 'ok']);
  }

  /**
   * Lists health status of all projects.
   *
   * @return array
   *   The render array.
   */
  public function health() : array {
    // We only care about health of test/stage/prod.
    $environments = [
      EnvMapping::Test,
      EnvMapping::Stage,
      EnvMapping::Prod,
    ];

    $header = [$this->t('Project')];

    foreach ($environments as $env) {
      $header[] = $env->label();
    }

    $build = [
      '#type' => 'table',
      '#header' => $header,
      '#attached' => [
        'library' => ['helfi_etusivu/health'],
      ],
    ];

    foreach ($this->environmentResolver->getProjects() as $name => $project) {
      $build[$name]['name'] = [
        '#markup' => $name,
      ];

      foreach ($environments as $env) {
        if (!$project->hasEnvironment($env->value)) {
          continue;
        }
        $build[$name][$env->value] = [
          '#wrapper_attributes' => [
            'data-environment' => $env->value,
            'data-project' => $name,
          ],
          '#markup' => $this->t('Fetching status...'),
        ];
      }
    }

    return $build;
  }

}
