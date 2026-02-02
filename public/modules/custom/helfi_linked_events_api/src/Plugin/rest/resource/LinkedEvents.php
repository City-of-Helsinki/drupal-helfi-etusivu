<?php

declare(strict_types=1);

namespace Drupal\helfi_linked_events_api\Plugin\rest\resource;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Hook\ImageThemeHooks;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A decorated Linked Events API event resource.
 */
#[RestResource(
  id: 'helfi_linked_events_api',
  label: new TranslatableMarkup('Linked Events API'),
  uri_paths: [
    'canonical' => '/api/v1/linked-events/event/{id}',
  ],
)]
class LinkedEvents extends ResourceBase {

  private const IMAGE_STYLE_NAMES = [
    '1.5_304w_203h',
    '1.5_294w_196h',
    '1.5_220w_147h',
    '1.5_176w_118h',
    '1.5_511w_341h',
  ];

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private RequestStack $requestStack;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  private Client $httpClient;

  /**
   * The image theme hooks.
   *
   * @var \Drupal\image\Hook\ImageThemeHooks
   */
  private ImageThemeHooks $imageThemeHooks;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->requestStack = $container->get('request_stack');
    $instance->httpClient = $container->get('http_client');
    $instance->imageThemeHooks = $container->get(ImageThemeHooks::class);

    return $instance;
  }

  /**
   * Callback for GET requests.
   *
   * @param string $id
   *   The id of the event.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function get(string $id): JsonResponse {
    $query = $this->requestStack->getCurrentRequest()->query->all();
    $query['format'] = 'json';
    $api_url = "https://api.hel.fi/linkedevents/v1/event/{$id}";

    $response = NULL;
    try {
      $response = $this->httpClient->request('GET', $api_url, [
        'query' => $query,
        'http_errors' => FALSE,
      ]);
      $data = json_decode($response->getBody()->getContents(), TRUE);
    }
    catch (RequestException $e) {
      $data = [];
    }

    // Decorate JSON response with image styles generated in Drupal.
    if (isset($data['data'])) {
      foreach ($data['data'] as &$item) {
        $this->generateImageStyles($item);
      }
    }
    else {
      $this->generateImageStyles($data);
    }

    // We need to use JsonResponse to allow json_encode with our own flags,
    // to make sure the json is as unaltered as possible.
    $response = new JsonResponse(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $response ? $response->getStatusCode() : 200, [], TRUE);
    $response->setMaxAge(10 * 60);

    return $response;
  }

  /**
   * Generate image styles from event image urls.
   */
  private function generateImageStyles(array &$data): void {
    $data['helfi_image_styles'] = [];

    if (!isset($data['images']) || empty($data['images'])) {
      return;
    }

    foreach ($data['images'] as $image) {
      if (empty($image['url']) || empty($image['id'])) {
        continue;
      }

      foreach (self::IMAGE_STYLE_NAMES as $style_name) {
        $image_style = ImageStyle::load($style_name);
        if (!$image_style) {
          continue;
        }

        $uri = imagecache_external_generate_path($image['url']);
        if (!$uri || !$image_style->supportsUri($uri)) {
          continue;
        }

        $data['helfi_image_styles'][$image['id']][$style_name] = $image_style->buildUrl($uri);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $collection = parent::routes();

    // Add defaults for optional parameters.
    $defaults = [
      'id' => '',
    ];
    foreach ($collection->all() as $route) {
      $route->addDefaults($defaults);
    }

    return $collection;
  }

}
