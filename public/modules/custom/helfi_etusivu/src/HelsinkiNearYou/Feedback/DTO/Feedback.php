<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO;

final readonly class Feedback {

  public function __construct(
    public string $status,
    public string $requested_datetime,
    public string $updated_datetime,
    public string $lat,
    public string $long,
    public ?string $address = NULL,
  ) {
  }

  public static function createFromArray(array $data) : self {
    $item = [];

    foreach (get_class_vars(self::class) as $property => $value) {
      if (!isset($data[$property])) {
        continue;
      }
      $item[$property] = $data[$property];
    }
    return new self(...$item);
  }

}
