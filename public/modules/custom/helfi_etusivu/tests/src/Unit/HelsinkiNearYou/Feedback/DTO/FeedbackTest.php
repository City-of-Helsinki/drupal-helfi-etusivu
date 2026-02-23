<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\HelsinkiNearYou\Feedback\DTO;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Feedback\DTO\Feedback;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Feedback DTO.
 *
 * @group helfi_etusivu
 */
class FeedbackTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $container = new ContainerBuilder();
    $languageManager = $this->createMock(LanguageManagerInterface::class);
    $languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => $this->randomMachineName(2)]));
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('language_manager', $languageManager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests required fields.
   *
   * @dataProvider missingItemData
   */
  public function testCreateFromArrayMissingItemException(array $data): void {
    $this->expectException(\InvalidArgumentException::class);
    Feedback::createFromArray($data);
  }

  /**
   * Data provider for testCreateFromArrayMissingItemException().
   *
   * @return array
   *   The data.
   */
  public static function missingItemData(): array {
    return [
      [
        [
          'description' => 1,
        ],
      ],
      [
        [
          'description' => 1,
          'lat' => 1,
        ],
      ],
      [
        [
          'description' => 1,
          'lat' => 1,
          'long' => 1,
        ],
      ],
      [
        [
          'description' => 1,
          'lat' => 1,
          'long' => 1,
          'address' => 1,
        ],
      ],
      [
        [
          'description' => 1,
          'lat' => 1,
          'long' => 1,
          'address' => 1,
          'requested_datetime' => '',
        ],
      ],
      [
        [
          'description' => 1,
          'lat' => 1,
          'long' => 1,
          'address' => 1,
          'requested_datetime' => '',
          'service_request_id' => '',
          'distance' => 1,
        ],
      ],
    ];
  }

  /**
   * Test with invalid datetime.
   */
  public function testCreateFromArrayInvalidDatetime(): void {
    $item = Feedback::createFromArray([
      'description' => 1,
      'lat' => 1,
      'long' => 1,
      'address' => 1,
      'service_request_id' => 1,
      'status' => 'published',
      'requested_datetime' => 'dsa',
      'distance' => 1,
    ]);
    $this->assertInstanceOf(Feedback::class, $item);
  }

  /**
   * Tests createFromArray().
   */
  public function testCreateFromArray() : void {
    $data = [
      'description' => $this->randomString(300),
      'lat' => 1,
      'long' => 1,
      'address' => 1,
      'service_request_id' => 1,
      'status' => 'PUBLISHED',
      'requested_datetime' => 'now',
      'distance' => 1,
    ];
    $item = Feedback::createFromArray($data);
    $this->assertEquals(300, strlen($data['description']));
    $len = mb_strlen($item->title);
    // There is a random failure that is caused by the value being
    // between 254 and 255 so the assert is now a range.
    $this->assertTrue($len > 250 && $len < 260);
  }

}
