<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\DTO;

final readonly class Address {

  public function __construct(
    public StreetName $streetName,
    public Location $location,
  ) {
  }

  public function __toString() : string {
    return $this->streetName->fi;
  }

}
