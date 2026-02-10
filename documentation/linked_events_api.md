# Linked Events Image API

## Base url

Base URL for the API endpoint is `https://www.hel.fi/linked-events/image/{image_id}`.

### Route parameters

- `image_id`: The Linked Events image id.

### Mandatory query parameters

These are query parameters instead of route parameters due to special characters that might cause issues when used in url paths.

- `style`: The image style name to use. Only allows image styles listed in `Drupal\helfi_etusivu\Controller\LinkedEventsImageController::IMAGE_STYLES_ALLOWED`.
- `time`: The `last_modified_time` image-key. This ensures the latest version of the image is received.

## Response

- On success: `\Drupal\Core\Cache\CacheableRedirectResponse` with status code 302
- On failure: `\Symfony\Component\HttpFoundation\Response` with status code 404

## What does it do?

1. Gets image data from Linked Events API endpoint `https://api.hel.fi/linkedevents/v1/image/{image_id}`.
2. Downloads image file to Drupal filesystem (with the help of `imagecache_external`-module).
3. Generates a image style derivative url for the image.
4. Returns a redirect to the generated image style url.
