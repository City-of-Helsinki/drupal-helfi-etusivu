<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\LinkedEvents\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\LinkedEvents\DTO\StyledImage;
use Drupal\image\ImageStyleInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a controller to redirect to linked events image styles.
 */
class LinkedEventsImageController implements ContainerInjectionInterface {

  use AutowireTrait;

  /**
   * Allowed image styles.
   */
  private const array IMAGE_STYLES_ALLOWED = [
    // Card responsive image style.
    '1_5_176w_118h',
    '1_5_220w_147h',
    '1_5_294w_196h',
    '1_5_304w_203h',
    '1_5_352w_236h_lq',
    '1_5_440w_294h_lq',
    '1_5_511w_341h',
    '1_5_588w_392h_lq',
    '1_5_608w_406w_lq',
    '1_5_1022w_682h_lq',
    // Card Teaser responsive image style.
    '1_5_217w_145h',
    '1_5_378w_252h',
    '1_5_405w_270h',
    '1_5_434w_290h_lq',
    '1_5_756w_504h_lq',
    '1_5_810w_540h_lq',
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
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $pageCacheKillSwitch
   *   The page cache kill switch.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ClientInterface $httpClient,
    #[Autowire(service: 'cache.default')]
    protected readonly CacheBackendInterface $cache,
    protected readonly TimeInterface $time,
    protected readonly KillSwitch $pageCacheKillSwitch,
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
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Symfony\Component\HttpFoundation\Response
   *   The redirect response or not found response.
   */
  public function deliver(Request $request, string $image_id): Response {
    $image_style = $request->query->get('style');
    $time = $request->query->get('time');

    if (!$image_style || !$time || !$image = $this->getImageStyleUrl($image_id, $image_style, $time)) {
      return $this->notFoundResponse();
    }

    // The time is only used to bypass the caches when the image is updated.
    // Don't let the page cache or the reverse proxy store the responses for
    // other times: the URLs are arbitrary, and after an update the cached
    // image is outdated until it's checked again. The kill switch also makes
    // the response uncacheable with the Cache-Control header.
    if ($image->lastModifiedTime !== $time) {
      $this->pageCacheKillSwitch->trigger();
    }

    // Return the image style url as a trusted redirect response.
    $response = new TrustedRedirectResponse($image->url, 302);
    $response->addCacheableDependency(
      new CacheableMetadata()->addCacheContexts([
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
   *   The last modified time of the image.
   *
   * @return \Drupal\helfi_etusivu\LinkedEvents\DTO\StyledImage|false
   *   The image style derivative of the Linked Events image, or false.
   */
  private function getImageStyleUrl(string $image_id, string $image_style, string $time): false|StyledImage {
    $cache_key = "linked_events_image_style_url:{$image_id}:{$image_style}";
    $now = $this->time->getRequestTime();

    // Use the cached image style url if it's for the requested time. Otherwise
    // check the image for updates at most once per interval.
    $cache = $this->cache->get($cache_key);
    if ($cache && $cache->data instanceof StyledImage && ($cache->data->lastModifiedTime === $time || $cache->created > $now - 60)) {
      return $cache->data;
    }

    // Make sure provided image style is allowed.
    if (!in_array($image_style, self::IMAGE_STYLES_ALLOWED)) {
      return FALSE;
    }

    // Make sure the image style exists.
    if (!$imageStyle = $this->entityTypeManager->getStorage('image_style')->load($image_style)) {
      return FALSE;
    }
    assert($imageStyle instanceof ImageStyleInterface);

    // Get the image url from the linked events api.
    $api_url = "https://api.hel.fi/linkedevents/v1/image/{$image_id}";
    $data = [];
    try {
      $response = $this->httpClient->request('GET', $api_url);
      $data = json_decode($response->getBody()->getContents(), TRUE);
    }
    catch (GuzzleException) {
    }

    // Make sure the image url is set.
    if (!is_array($data) || empty($data['url']) || !is_string($data['url'])) {
      return FALSE;
    }
    $last_modified_time = (string) ($data['last_modified_time'] ?? '');

    $image_url = Url::fromUri($data['url'], [
      'query' => [
        // Add time query parameter to the image url to make sure the original
        // is refetched if the image has updated.
        'time' => $last_modified_time,
      ],
      'absolute' => TRUE,
    ]);

    // Download the image into Drupal filesystem.
    $uri = $this->downloadExternalImage($image_url->toString());
    if (!$uri || !$imageStyle->supportsUri($uri)) {
      return FALSE;
    }

    // Generate, cache and return the image style url.
    $image = new StyledImage($imageStyle->buildUrl($uri), $last_modified_time);
    $this->cache->set($cache_key, $image);
    return $image;
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
