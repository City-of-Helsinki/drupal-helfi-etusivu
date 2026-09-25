<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Search\Numerot;

use Drupal\helfi_etusivu\Plugin\migrate\source\NumerotOrganizations;
use Drupal\migrate\MigrateException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the numerot.hel.fi organizations source plugin.
 */
#[CoversClass(NumerotOrganizations::class)]
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
final class NumerotOrganizationsTest extends NumerotSourceTestBase {

  private const string LEAF = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
  private const string OTHER_LEAF = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
  private const string ROOT = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
  private const string BRANCH = 'dddddddd-dddd-4ddd-8ddd-dddddddddddd';

  /**
   * Gets the URL for a language.
   */
  private static function url(string $langcode): string {
    return "https://numerot.hel.fi/$langcode/jsonapi/taxonomy_term/ou?filter[status]=1&sort=drupal_internal__tid&page[limit]=50&fields[taxonomy_term--ou]=name,weight,parent,langcode";
  }

  /**
   * Builds an expected row.
   *
   * @phpstan-return array<string, string|int|null>
   */
  private static function row(string $id, string $langcode, ?string $name, int $weight, ?string $parent): array {
    return [
      'id' => $id,
      'language' => $langcode,
      'name' => $name,
      'weight' => $weight,
      'parent_id' => $parent,
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, array<string, mixed>>
   *   The test cases.
   */
  public static function providerSource(): array {
    $names = [
      'fi' => ['Testiyksikkö', 'Testitoimiala'],
      'sv' => ['Testenhet', 'Testsektorn'],
      'en' => ['Test unit', 'Test division'],
    ];

    $expected = [];
    foreach ($names as $langcode => [$child, $parent]) {
      $expected[] = self::row(self::LEAF, $langcode, $child, 0, self::BRANCH);
      $expected[] = self::row(self::OTHER_LEAF, $langcode, 'Toinen yksikkö', 1, self::ROOT);
      $expected[] = self::row(self::BRANCH, $langcode, 'Testipalvelu', 0, self::ROOT);
      $expected[] = self::row(self::ROOT, $langcode, $parent, 0, NULL);
    }

    $tests['full'] = [
      'source_data' => [
        self::url('fi') => 'ou-fi.json',
        self::url('sv') => 'ou-sv.json',
        self::url('en') => 'ou-en.json',
      ],
      'expected_data' => $expected,
    ];

    return $tests;
  }

  /**
   * Tests that a failed request fails the whole feed.
   */
  public function testFailure(): void {
    $this->setSourceData([
      self::url('fi') => 'ou-fi.json',
    ]);

    $this->expectException(MigrateException::class);
    $this->getRows();
  }

}
