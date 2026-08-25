<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_api_base\Environment\Address;
use Drupal\helfi_api_base\Environment\Environment;
use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Environment\EnvironmentResolverInterface;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\helfi_api_base\ServiceMap\ServiceMapInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\EventsController;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\Client;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\LazyBuilder;
use GuzzleHttp\ClientInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Kernel test for EventsController::content().
 *
 * @group helfi_etusivu
 */
class EventsControllerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_api_base',
    'diff',
    'helfi_etusivu',
    'system',
  ];

  /**
   * The mocked environment resolver.
   */
  protected MockObject&EnvironmentResolverInterface $environmentResolver;

  /**
   * Builds a real Environment value object with the given base URL domain.
   */
  private function makeEnvironment(string $domain): Environment {
    $address = new Address($domain);
    return new Environment($address, $address, [], EnvironmentEnum::Local);
  }

  /**
   * Builds a controller instance wired with the given resolver mock.
   */
  private function makeController(): EventsController {
    // LazyBuilder and Client are final; construct real instances.
    // content() never invokes build(), so the http client stays idle.
    $lazyBuilder = new LazyBuilder(
      new Client($this->createMock(ClientInterface::class)),
    );
    return new EventsController(
      $lazyBuilder,
      $this->environmentResolver,
      $this->createMock(ServiceMapInterface::class),
      $this->container->get(FormBuilderInterface::class),
      $this->container->get(LanguageManagerInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->environmentResolver = $this->createMock(EnvironmentResolverInterface::class);
  }

  /**
   * Tests that etusivuBaseUrl is set when the current environment is found.
   */
  public function testContentSetsEtusivuBaseUrlForCurrentEnvironment(): void {
    $this->environmentResolver
      ->method('getActiveEnvironmentName')
      ->willReturn('local');

    $this->environmentResolver
      ->method('getEnvironment')
      ->with(Project::ETUSIVU, 'local')
      ->willReturn($this->makeEnvironment('helfi-proxy.docker.so'));

    $build = $this->makeController()->content();

    $this->assertEquals('helsinki_near_you_events', $build['#theme']);
    $this->assertEquals(
      'https://helfi-proxy.docker.so',
      $build['#attached']['drupalSettings']['helfi_events']['etusivuBaseUrl'],
    );
  }

  /**
   * Tests that etusivuBaseUrl falls back to prod when the env is unknown.
   */
  public function testContentFallsBackToProdWhenEnvironmentNotFound(): void {
    $this->environmentResolver
      ->method('getActiveEnvironmentName')
      ->willReturn('unknown');

    $this->environmentResolver
      ->method('getEnvironment')
      ->willReturnCallback(function (string $project, string $env) {
        if ($env === 'unknown') {
          throw new \InvalidArgumentException('No environment');
        }
        // Called with EnvironmentEnum::Prod->value as fallback.
        return $this->makeEnvironment('www.hel.fi');
      });

    $build = $this->makeController()->content();

    $this->assertEquals(
      'https://www.hel.fi',
      $build['#attached']['drupalSettings']['helfi_events']['etusivuBaseUrl'],
    );
  }

  /**
   * Tests that etusivuBaseUrl is absent when the resolver throws unexpectedly.
   */
  public function testContentOmitsEtusivuBaseUrlOnServiceFailure(): void {
    $this->environmentResolver
      ->method('getActiveEnvironmentName')
      ->willThrowException(new \RuntimeException('Service unavailable'));

    $build = $this->makeController()->content();

    $this->assertArrayNotHasKey(
      'etusivuBaseUrl',
      $build['#attached']['drupalSettings']['helfi_events'],
    );
  }

  /**
   * Tests that the other drupalSettings keys are always present.
   */
  public function testContentAlwaysIncludesRequiredSettings(): void {
    $this->environmentResolver
      ->method('getActiveEnvironmentName')
      ->willReturn('local');
    $this->environmentResolver
      ->method('getEnvironment')
      ->willReturn($this->makeEnvironment('helfi-proxy.docker.so'));

    $build = $this->makeController()->content();

    $settings = $build['#attached']['drupalSettings']['helfi_events'];
    $this->assertArrayHasKey('baseUrl', $settings);
    $this->assertArrayHasKey('data', $settings);
    $this->assertArrayHasKey('helfi-coordinates-based-event-list', $settings['data']);
    $this->assertArrayHasKey('seeAllButtonOverride', $settings);
  }

}
