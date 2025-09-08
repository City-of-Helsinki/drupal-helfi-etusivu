<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\LinkedEvents;

use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO\Event;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Event DTO.
 *
 * @group helfi_etusivu
 */
class EventTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_etusivu',
    'big_pipe',
    'system',
  ];

  /**
   * Tests createFromArray.
   */
  public function testCreateFromArray() : void {
    $data = [
      'id' => '123',
      'name' => [
        'fi' => 'title fi',
      ],
      'type_id' => 'Volunteering',
      'offers' => [],
      'location' => [
        'id' => 'test',
      ],
      'images' => [],
      'enrolment_start_time' => NULL,
      'enrolment_end_time' => NULL,
      'start_time' => NULL,
      'end_time' => NULL,
    ];

    $sut = Event::createFromArray('sv', $data);
    $this->assertEquals('title fi', $sut->title);
    $this->assertNull($sut->category);
    $this->assertEmpty($sut->tags);
    $this->assertFalse($sut->isRemote);
    $this->assertNull($sut->enrolmentStartDate);
    $this->assertNull($sut->enrolmentEndDate);
    $this->assertNull($sut->startDate);
    $this->assertNull($sut->endDate);
    $this->assertFalse($sut->registrationRequired);
    $this->assertNull($sut->image);
    $this->assertEquals('', $sut->location);
    $this->assertEquals('https://tapahtumat.hel.fi/sv/kurser/123', $sut->uri->toString());
    $this->assertFalse($sut->isMultiDate);

    $data = array_merge($data, [
      'location' => ['id' => 'helsinki:internet'],
      'type_id' => 'Course',
      'offers' => [
        ['is_free' => TRUE],
        ['info_url' => ['sv' => 'https://localhost']],
      ],
      'images' => [
        ['url' => 'https://localhost/kuva.jpg', 'alt_text' => '123', 'photographer_name' => 'name'],
      ],
      'enrolment_start_time' => '2004-02-12T15:19:21+00:00',
      'enrolment_end_time' => '2004-02-12T15:19:21+00:00',
      'start_time' => '2004-02-12T15:19:21+00:00',
      'end_time' => '2004-02-15T15:19:21+00:00',
    ]);

    $sut = Event::createFromArray('sv', $data);

    $this->assertEquals('https://harrastukset.hel.fi/sv/kurser/123', $sut->uri->toString());
    $this->assertEquals('123', $sut->image->alt);
    $this->assertEquals('name', $sut->image->photographer);
    $this->assertEquals('https://localhost/kuva.jpg', $sut->image->url);
    $this->assertEquals('Internet', $sut->location);
    $this->assertTrue($sut->isRemote);
    $this->assertTrue($sut->isFree);
    $this->assertTrue($sut->isMultiDate);
  }

}
