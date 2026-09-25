<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Search\Numerot;

use Drupal\helfi_etusivu\Plugin\migrate\source\NumerotContactDetails;
use Drupal\migrate\MigrateException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the numerot.hel.fi contact details source plugin.
 */
#[CoversClass(NumerotContactDetails::class)]
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
final class NumerotContactDetailsTest extends NumerotSourceTestBase {

  private const string URL = 'https://numerot.hel.fi/fi/jsonapi/node/person?filter[status]=1&sort=drupal_internal__nid&page[limit]=50';
  private const string PAGE_2_URL = 'https://numerot.hel.fi/fi/jsonapi/node/person?filter[status]=1&page[offset]=2&page[limit]=2';

  /**
   * {@inheritdoc}
   *
   * @return array<string, array<string, mixed>>
   *   The test cases.
   */
  public static function providerSource(): array {
    $matti = [
      'id' => '11111111-1111-4111-8111-111111111111',
      'name_parts' => ['Matti', 'Meikäläinen'],
      'email' => 'matti.meikalainen@example.com',
      // Non-public phone numbers are left out.
      'phones' => [
        ['value' => '+358 9 123 4567', 'type' => 'landline', 'info' => NULL],
        ['value' => '+358 9 310 2222', 'type' => 'landline', 'info' => 'virkanumero'],
      ],
      'addresses' => [
        ['value' => "Testikatu 1\n00100 Helsinki", 'type' => 'street', 'info' => NULL],
        ['value' => "PL 1\n00099 Helsingin kaupunki", 'type' => 'mail', 'info' => NULL],
      ],
      'organization_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
    ];
    $maija = [
      'id' => '22222222-2222-4222-8222-222222222222',
      'name_parts' => ['Maija', 'Virtanen'],
      'email' => NULL,
      'phones' => [
        ['value' => '+358 9 310 1111', 'type' => 'customer_service', 'info' => NULL],
      ],
      'addresses' => [],
      'organization_id' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
      'service_hours' => NULL,
    ];
    $kalle = [
      'id' => '33333333-3333-4333-8333-333333333333',
      'name_parts' => ['Kalle', 'Korhonen'],
      'email' => 'kalle.korhonen@example.com',
      'phones' => [],
      'addresses' => [],
      'organization_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
      'service_hours' => NULL,
    ];

    $tests['full'] = [
      'source_data' => [
        self::URL => 'person-page-1.json',
        self::PAGE_2_URL => 'person-page-2.json',
      ],
      'expected_data' => [
        $matti + ['language' => 'fi', 'job_title' => 'Suunnittelija', 'service_hours' => 'ma–pe 9–12'],
        $matti + ['language' => 'sv', 'job_title' => 'Planerare', 'service_hours' => 'mån–fre 9–12'],
        $matti + ['language' => 'en', 'job_title' => 'Planner', 'service_hours' => 'Mon–Fri 9–12'],
        $maija + ['language' => 'fi', 'job_title' => 'Asiakaspalvelija'],
        $maija + ['language' => 'sv', 'job_title' => NULL],
        $maija + ['language' => 'en', 'job_title' => NULL],
        $kalle + ['language' => 'fi', 'job_title' => 'Johtaja'],
        $kalle + ['language' => 'sv', 'job_title' => 'Direktör'],
        $kalle + ['language' => 'en', 'job_title' => 'Director'],
      ],
    ];

    $tests['malformed values'] = [
      'source_data' => [
        self::URL => 'person-malformed.json',
      ],
      'expected_data' => [
        [
          'id' => 'broken',
          'language' => 'fi',
          'name_parts' => ['Nimi'],
          'email' => NULL,
          'phones' => [['value' => '+358 9 123', 'type' => NULL, 'info' => NULL]],
          'addresses' => [],
          'job_title' => NULL,
          'organization_id' => NULL,
        ],
        ['id' => 'broken', 'language' => 'sv'],
        ['id' => 'broken', 'language' => 'en'],
        [
          'id' => 'empty',
          'language' => 'fi',
          'name_parts' => [],
          'phones' => [],
          'addresses' => [],
          'organization_id' => NULL,
        ],
        ['id' => 'empty', 'language' => 'sv'],
        ['id' => 'empty', 'language' => 'en'],
      ],
    ];

    return $tests;
  }

  /**
   * Tests failed requests.
   *
   * @param array<string, string> $fixtures
   *   The fixture names keyed by URL.
   * @param string $message
   *   The expected exception message.
   */
  #[DataProvider('providerFailure')]
  public function testFailure(array $fixtures, string $message): void {
    $this->setSourceData($fixtures);

    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage($message);
    $this->getRows();
  }

  /**
   * Data provider for ::testFailure().
   *
   * @return array<string, array{array<string, string>, string}>
   *   The test cases.
   */
  public static function providerFailure(): array {
    return [
      'missing page 2' => [
        [self::URL => 'person-page-1.json'],
        '404 Not Found',
      ],
      'empty feed' => [
        [self::URL => 'empty.json'],
        'The numerot.hel.fi API returned no published items.',
      ],
    ];
  }

}
