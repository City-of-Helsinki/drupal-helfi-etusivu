<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * A DTO to represent Feedback item.
 */
final readonly class Feedback {

  public function __construct(
    public Status $status,
    public string $description,
    public DrupalDateTime $requested_datetime,
    public string $lat,
    public string $long,
    public string $uri,
    public int $distance,
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
    $required = [
      'description',
      'lat',
      'long',
      'address',
      'requested_datetime',
      'service_request_id',
      'status',
      'distance',
    ];
    foreach ($required as $key) {
      if (!isset($data[$key])) {
        throw new \InvalidArgumentException(sprintf('Missing %s', $key));
      }
    }
    $item = [
      'distance' => $data['distance'],
      'title' => Unicode::truncate((string) $data['description'], 253, add_ellipsis: TRUE),
      'description' => (string) $data['description'],
      'uri' => sprintf('https://palautteet.hel.fi/kartalla-julkaistu-palaute#/published/%s', $data['service_request_id']),
      'requested_datetime' => new DrupalDateTime($data['requested_datetime']),
      'lat' => (string) $data['lat'],
      'long' => (string) $data['long'],
      'address' => (string) $data['address'],
      'status' => Status::tryFrom($data['status']) ?: Status::Unknown,
    ];

    return new self(...$item);
  }

}
