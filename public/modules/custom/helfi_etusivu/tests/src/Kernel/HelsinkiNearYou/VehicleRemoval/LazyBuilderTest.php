<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\VehicleRemoval;

use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_api_base\ServiceMap\DTO\StreetName;
use Drupal\helfi_etusivu\HelsinkiNearYou\Enum\InternalSearchLink;
use Drupal\helfi_etusivu\HelsinkiNearYou\VehicleRemoval\LazyBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\helfi_api_base\Traits\EnvironmentResolverTrait;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the vehicle removal lazy builder.
 */
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
class LazyBuilderTest extends KernelTestBase {

  use ApiTestTrait;
  use EnvironmentResolverTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_api_base',
    'diff',
    'helfi_etusivu',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setActiveProject(Project::ETUSIVU, EnvironmentEnum::Local);
  }

  /**
   * Tests the postal code result.
   */
  #[RunInSeparateProcess]
  public function testPostalCodes(): void {
    $build = $this->build(new Response(200, body: (string) json_encode([
      'postalcodes' => '00100,00130',
      'results' => [
        [
          '_source' => [
            'address' => ['Mannerheimintie 1'],
            'valid_from' => [1767225600],
            'valid_to' => [1767484800],
            'map_url' => ['https://kartta.hel.fi/?id=1'],
            'time_range' => ['8-18'],
          ],
        ],
      ],
    ])));

    $content = $build['#content'];
    $url = $content['#button_url'];
    $description = $content['#description']->getUntranslatedString();

    $baseUrl = InternalSearchLink::VehicleRemoval->getLinkTranslation('en');
    $this->assertNotEmpty($baseUrl);
    $this->assertStringStartsWith($baseUrl, $url);
    $this->assertEquals('See vehicle removal requests in your postal code area.', $description);
    $this->assertEquals('01.01.2026 - 04.01.2026', $content['#items'][0]['#date_range']);
    $this->assertTrue(str_contains($url, 'postal_code'));
    $this->assertTrue(str_contains($url, '00100'));
    $this->assertTrue(str_contains($url, '00130'));
  }

  /**
   * Tests the parkingzone result.
   */
  #[RunInSeparateProcess]
  public function testParkingZone(): void {
    $build = $this->build(new Response(200, body: (string) json_encode([
      'parkingzone' => 'Kluuvi/Kruununhaka',
      'results' => [
        [
          '_source' => [
            'address' => ['Mannerheimintie 1'],
            'valid_from' => [1767225600],
            'valid_to' => [1767484800],
            'map_url' => ['https://kartta.hel.fi/?id=1'],
            'time_range' => ['8-18'],
          ],
        ],
      ],
    ])));

    $content = $build['#content'];
    $url = $content['#button_url'];
    $description = $content['#description']->getUntranslatedString();

    $baseUrl = InternalSearchLink::VehicleRemoval->getLinkTranslation('en');
    $this->assertNotEmpty($baseUrl);
    $this->assertStringStartsWith($baseUrl, $url);
    $this->assertEquals('See vehicle removal requests in your parking zone.', $description);
    $this->assertTrue(str_contains($url, 'parking_zones'));
    $this->assertTrue(str_contains($url, 'Kluuvi/Kruununhaka'));
  }

  /**
   * Builds the section with the given API response.
   *
   * @return array<mixed>
   *   The render array.
   */
  private function build(Response $response): array {
    $this->container->set('http_client', $this->createMockHttpClient([$response]));

    $address = new Address(
      StreetName::createFromArray(['fi' => 'Mannerheimintie']),
      new Location(60.17, 24.94, 'Point'),
    );

    return $this->container->get(LazyBuilder::class)->build($address);
  }

}
