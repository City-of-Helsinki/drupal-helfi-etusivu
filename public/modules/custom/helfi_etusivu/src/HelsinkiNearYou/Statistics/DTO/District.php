<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO;

/**
 * A DTO to store a statistical district.
 *
 * The code is the Servicemap 'statistical_district' origin_id, which is also
 * the area code used by the Aluesarjat PxWeb API.
 *
 * Names are stored per language like
 * \Drupal\helfi_api_base\ServiceMap\DTO\StreetName, so that the presentation
 * layer chooses the language.
 */
final readonly class District {

  public function __construct(
    public string $code,
    public string $fi,
    public string $sv,
    public string $en,
  ) {
  }

  /**
   * Creates an instance from an area code and its names.
   *
   * @param string $code
   *   The area code.
   * @param array $names
   *   Names keyed by langcode. Must contain 'fi', the only language every
   *   source provides. These are place names rather than translations, and
   *   Helsinki districts have no English ones.
   *
   * @return self
   *   A new instance.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the code or the Finnish name is missing.
   */
  public static function create(string $code, array $names) : self {
    if (empty($code)) {
      throw new \InvalidArgumentException('Missing area code.');
    }
    if (empty($names['fi'])) {
      throw new \InvalidArgumentException('Missing "fi" name.');
    }

    return new self(
      $code,
      (string) $names['fi'],
      (string) ($names['sv'] ?? $names['fi']),
      (string) ($names['en'] ?? $names['fi']),
    );
  }

  /**
   * Gets the district name for the given language.
   *
   * @param string $language
   *   The langcode.
   *
   * @return string
   *   The name, falling back to Finnish.
   */
  public function getName(string $language) : string {
    return match ($language) {
      'sv' => $this->sv,
      'en' => $this->en,
      default => $this->fi,
    };
  }

}
