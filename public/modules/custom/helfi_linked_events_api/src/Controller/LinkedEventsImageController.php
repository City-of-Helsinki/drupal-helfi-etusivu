<?php

declare(strict_types=1);

namespace Drupal\helfi_linked_events_api\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\image\ImageStyleInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a controller to redirect to linked events image styles.
 */
class LinkedEventsImageController implements ContainerInjectionInterface {

  use AutowireTrait;

  private const IMAGE_STYLES_ALLOWED = [
    '1.5_511w_341h',
  ];

  /**
   * Constructs an LinkedEventsImageController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ClientInterface $httpClient,
    #[Autowire(service: 'cache.default')]
    protected readonly CacheBackendInterface $cache,
  ) {
  }

  /**
   * Redirects to a linked events image style.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $image_id
   *   The linked events image id.
   *
   * @return \Drupal\Core\Cache\CacheableRedirectResponse|\Symfony\Component\HttpFoundation\Response
   *   The redirect response or not found response.
   */
  public function deliver(Request $request, string $image_id): Response {
    // Get and validate query parameters for image style and time.
    $image_style = $this->getQueryParameterValue($request, 'style', '/^[0-9a-zA-Z_.\-]+$/');
    $time = $this->getQueryParameterValue($request, 'time', '/^[0-9TZ.:\-]+$/');
    if (!$image_style || !$time) {
      return $this->notFoundResponse();
    }

    // Get the image style url.
    $image_url = $this->getImageStyleUrl($image_id, $image_style, $time);
    if (!$image_url) {
      return $this->notFoundResponse();
    }

    // Return the image style url as a redirect response.
    $response = new CacheableRedirectResponse($image_url, 302);
    $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts([
      'url',
    ]));
    return $response;
  }

  /**
   * Gets the linked events image style url.
   *
   * @param string $image_id
   *   The linked events image id.
   * @param string $image_style
   *   The image style to deliver.
   * @param string $time
   *   The time of the image.
   *
   * @return string
   *   The linked events image style url.
   */
  private function getImageStyleUrl(string $image_id, string $image_style, string $time): string {
    $cache_key = "linked_events_image_style_url:{$image_id}:{$image_style}:{$time}";

    // Try to get the image style url from cache first.
    $cache = $this->cache->get($cache_key);
    if ($cache) {
      return $cache->data;
    }

    // Make provided image style is allowed.
    if (!in_array($image_style, self::IMAGE_STYLES_ALLOWED)) {
      return '';
    }

    // Make sure the image style exists.
    $imageStyle = $this->entityTypeManager->getStorage('image_style')->load($image_style);
    if (!$imageStyle) {
      return '';
    }
    assert($imageStyle instanceof ImageStyleInterface);

    // Get the image url from the linked events api.
    $api_url = "https://api.hel.fi/linkedevents/v1/image/{$image_id}";
    $data = [];
    try {
      $response = $this->httpClient->request('GET', $api_url);
      $data = json_decode($response->getBody()->getContents(), TRUE);
    }
    catch (RequestException $e) {
    }

    // Make sure the image url is set.
    if (empty($data['url'])) {
      return '';
    }

    // Add time query parameter to the image url to make sure the original is
    // refetched if the time parameter has changed. But only if the time
    // matches the last_modified_time of the image, to prevent a DoS attack
    // where the attacker could keep requesting the same image with different
    // time parameters.
    $latest_time = $data['last_modified_time'] ?? NULL;
    $options = [
      'absolute' => TRUE,
    ];
    if ($time === $latest_time) {
      $options['query'] = [
        'time' => $time,
      ];
    }
    $image_url = Url::fromUri($data['url'], $options);

    // Download the image into Drupal filesystem.
    $uri = $this->downloadExternalImage($image_url->toString());
    if (!$uri || !$imageStyle->supportsUri($uri)) {
      return '';
    }

    // Generate, cache and return the image style url.
    $image_style_url = $imageStyle->buildUrl($uri);
    $this->cache->set($cache_key, $image_style_url);
    return $image_style_url;
  }

  /**
   * Get and validate a query parameter value.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $name
   *   The name of the query parameter.
   * @param string $pattern
   *   The regex pattern to validate the query parameter value against.
   *
   * @return string
   *   The query parameter value.
   */
  private function getQueryParameterValue(Request $request, string $name, string $pattern): string {
    $value = $request->query->get($name);
    if (!$value || !preg_match($pattern, $value)) {
      return '';
    }

    return $value;
  }

  /**
   * Download external image into Drupal filesystem.
   *
   * @param string $url
   *   The url of the external image.
   *
   * @return bool|string
   *   The uri of the downloaded image.
   */
  protected function downloadExternalImage(string $url): bool|string {
    return imagecache_external_generate_path($url);
  }

  /**
   * Not found response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The not found response.
   */
  private function notFoundResponse(): Response {
    return new Response('Image not found', 404);
  }

}
