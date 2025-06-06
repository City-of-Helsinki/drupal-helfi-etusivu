<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents;
use Drupal\KernelTests\KernelTestBase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests linked events helper service.
 */
class LinkedEventsTest extends KernelTestBase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'helfi_etusivu',
  ];

  /**
   * Tests getEventsRequest method.
   */
  public function testEventsRequest(): void {
    $language = new Language(['id' => 'en']);

    $languageManager = $this->prophesize(LanguageManagerInterface::class);
    $languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->willReturn($language);

    $linkedEvents = new LinkedEvents($languageManager->reveal());
    $url = $linkedEvents->getEventsRequest();

    $this->assertEquals('https://api.hel.fi/linkedevents/v1/event/?event_type=General&format=json&include=keywords%2Clocation&page=1&page_size=3&sort=end_time&start=now&super_event_type=umbrella%2Cnone&language=en&all_ongoing=true', $url);
  }

}
