<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO;

/**
 * The target group.
 */
enum TargetGroup {

  case Adult;

  /**
   * Gets the default query filters for given target group.
   *
   * These are copied from React (TargetGroup.ts).
   *
   * @return array
   *   The query filters.
   */
  public function getQueryFilters(): array {
    return match ($this) {
      self::Adult => [
        'keyword!' => implode(',', [
          // Palvelukeskus.
          'helsinki:aflfbat76e',
          // Youth.
          'yso:p11617',
          // Families with babies.
          'yso:p13050',
          // School students.
          'yso:p1648',
          // Families with babies.
          'yso:p20513',
          // Seniors.
          'yso:p2433',
          // Children.
          'yso:p4354',
          // Elementary school students (Peruskoululaiset).
          'yso:p16485',
          // Elementary school studentes (Alakoululaiset) duplicate tag.
          'yso:p38259',
          // Children (age groups) a duplicate children tag?
          'yso:p4354',
          // Playgrounds.
          'yso:p8105',
        ]),
      ],
    };
  }

}
