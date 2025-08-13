<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\DTO;

final readonly class StreetName {

  public function __construct(
    public string $fi,
    public string $sv,
    public string $en,
  ) {
  }

  public static function createFromArray(array $data): self {
    $item = [];

    if (!isset($data['fi'])) {
      throw new \InvalidArgumentException('Missing "fi" parameter.');
    }
    foreach (get_class_vars(self::class) as $key => $value) {
      $item[$key] = $data[$key] ?? $data['fi'];
    }

    return new self(...$item);
  }

  /**
   * Gets the street name for given language.
   *
   * @param string $language
   *   The language to get name for.
   *
   * @return string
   *   The street name.
   */
  public function getName(string $language) : string {
    if (!property_exists($this, $language)) {
      return $this->fi;
    }
    return $this->{$language};
  }

}
