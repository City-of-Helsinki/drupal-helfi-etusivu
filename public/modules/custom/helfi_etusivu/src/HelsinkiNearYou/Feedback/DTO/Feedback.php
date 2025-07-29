<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * A DTO to represent Feedback item.
 */
final readonly class Feedback {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Status $status
   *   The status.
   * @param string $description
   *   The description.
   * @param \Drupal\Core\Datetime\DrupalDateTime $requested_datetime
   *   The requested datetime.
   * @param string $lat
   *   The latitude.
   * @param string $long
   *   The longitude.
   * @param string $uri
   *   The uri.
   * @param string|null $title
   *   The title.
   * @param string|null $address
   *   The address.
   */
  public function __construct(
    public Status $status,
    public string $description,
    public DrupalDateTime $requested_datetime,
    public string $lat,
    public string $long,
    public string $uri,
    public ?string $title,
    public ?string $address = NULL,
  ) {
  }

  /**
   * Constructs a new instance from given array.
   *
   * @param array $data
   *   The data.
   *
   * @return self
   *   The self.
   */
  public static function createFromArray(array $data) : self {
    $item = [];

    foreach (['description', 'lat', 'long', 'address'] as $key) {
      if (!isset($data[$key])) {
        throw new \InvalidArgumentException(sprintf('Missing %s', $key));
      }
      $item[$key] = $data[$key];
    }

    $item['uri'] = sprintf('https://palautteet.hel.fi/kartalla-julkaistu-palaute#/published/%s', $data['service_request_id']);

    $item['status'] = Status::tryFrom($data['status']);

    if ($item['status'] === NULL) {
      $item['status'] = Status::Unknown;
    }

    if (isset($data['requested_datetime'])) {
      $item['requested_datetime'] = new DrupalDateTime($data['requested_datetime']);
    }

    // Use description as a fallback title.
    $item['title'] = Unicode::truncate($item['description'], 255, add_ellipsis: TRUE);

    if (isset($data['extended_attributes']['title'])) {
      $item['title'] = $data['extended_attributes']['title'];
    }

    return new self(...$item);
  }

}
