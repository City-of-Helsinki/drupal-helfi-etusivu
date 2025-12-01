<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\DTO;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Runtime information for Helsinki near you hero.
 *
 * Static information is stored in RouteInformationEnum.
 *
 * @see \Drupal\helfi_etusivu\HelsinkiNearYou\Enum\RouteInformationEnum
 */
final readonly class HeroOptions {

  public function __construct(
    public array $translationArguments = [],
    public ?array $form = NULL,
    public ?CacheableMetadata $cache = NULL,
  ) {
  }

}
