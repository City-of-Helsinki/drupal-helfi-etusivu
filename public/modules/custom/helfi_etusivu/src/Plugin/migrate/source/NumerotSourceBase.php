<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\migrate\source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\AutowiredInstanceTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for numerot.hel.fi JSON:API source plugins.
 */
abstract class NumerotSourceBase extends SourcePluginBase implements ContainerFactoryPluginInterface {

  use AutowiredInstanceTrait;

  /**
   * The imported languages. The first one is the default translation.
   */
  protected const array LANGUAGES = ['fi', 'sv', 'en'];

  /**
   * The API base URL.
   */
  protected const string BASE_URL = 'https://numerot.hel.fi';

  /**
   * Constructs a new instance.
   *
   * @phpstan-param array<string, mixed> $configuration
   * @phpstan-param string $plugin_id
   * @phpstan-param mixed $plugin_definition
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    #[Autowire('logger.channel.helfi_etusivu')]
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   * @phpstan-param string $plugin_id
   * @phpstan-param mixed $plugin_definition
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ?MigrationInterface $migration = NULL,
  ): static {
    // @fixme https://www.drupal.org/project/drupal/issues/3578734.
    return static::createInstanceAutowired($container, $configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * Gets the first page URLs.
   *
   * The URLs must sort by a unique field. Offset pagination is not stable
   * without it, and pages skip some items while repeating others.
   *
   * @return array<string, string>
   *   The URLs keyed by language.
   */
  abstract protected function getUrls(): array;

  /**
   * Converts a JSON:API response to source rows.
   *
   * @param \stdClass $content
   *   The response.
   * @param string $language
   *   The language of the requested URL.
   *
   * @return iterable<array<string, mixed>>
   *   The source rows.
   */
  abstract protected function parseResponse(\stdClass $content, string $language): iterable;

  /**
   * Fetches a JSON:API response.
   *
   * @param string $url
   *   The URL.
   *
   * @return \stdClass
   *   The response.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  private function request(string $url): \stdClass {
    $config = $this->configFactory->get('helfi_etusivu.numerot');
    $apiKey = $config->get('api_key');
    $appId = $config->get('app_id');

    if (!$apiKey || !$appId) {
      throw new MigrateException('The "numerot.hel.fi" settings are not configured.');
    }

    try {
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'api-key' => $apiKey,
          'auth-method' => $appId,
          'Accept' => 'application/json',
        ],
      ]);

      $content = json_decode((string) $response->getBody(), flags: JSON_THROW_ON_ERROR);
    }
    catch (GuzzleException | \JsonException $e) {
      throw new MigrateException($e->getMessage(), previous: $e);
    }

    if (!$content instanceof \stdClass || !isset($content->data)) {
      throw new MigrateException('Unexpected response');
    }

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  final protected function initializeIterator(): \Iterator {
    $processed = 0;

    foreach ($this->getUrls() as $language => $url) {
      while ($url) {
        $this->logger->info("Fetching $url");

        $content = $this->request($url);
        $url = $content->links->next->href ?? NULL;

        foreach ($this->parseResponse($content, $language) as $row) {
          $processed++;
          yield $row;
        }
      }
    }

    if ($processed === 0) {
      throw new MigrateException('The numerot.hel.fi API returned no published items.');
    }
  }

  /**
   * Normalizes a string or a formatted text value.
   *
   * @param mixed $value
   *   The attribute value.
   *
   * @return string|null
   *   The trimmed value, or NULL if empty.
   */
  protected function text(mixed $value): ?string {
    if (!is_scalar($value)) {
      return NULL;
    }
    $value = trim((string) $value);

    return $value === '' ? NULL : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return static::class;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string, array<string, string>>
   */
  public function getIds(): array {
    return [
      'id' => [
        'type' => 'string',
      ],
      'language' => [
        'type' => 'string',
        'entity_key' => 'langcode',
      ],
    ];
  }

}
