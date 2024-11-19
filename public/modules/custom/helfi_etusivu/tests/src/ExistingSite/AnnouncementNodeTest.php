<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\ExistingSite;

use Drupal\helfi_etusivu\Entity\Node\Announcement;
use Drupal\Tests\helfi_api_base\Functional\ExistingSiteTestBase;

/**
 * Tests announcement node.
 *
 * @group helfi_etusivu
 */
class AnnouncementNodeTest extends ExistingSiteTestBase {

  /**
   * Tests 'field_publish_externally'.
   */
  public function testGlobalAnnouncementNodeCreation() : void {
    /** @var \Drupal\helfi_etusivu\Entity\Node\Announcement $announcement */
    $announcement = $this->createNode([
      'type' => 'announcement',
      'title' => 'Test announcement',
    ]);
    $announcement->save();
    $this->assertTrue($announcement instanceof Announcement);

    $this->assertFalse($announcement->publishExternally());

    /** @var \Drupal\helfi_etusivu\Entity\Node\Announcement $globalAnnouncement */
    $globalAnnouncement = $this->createNode([
      'type' => 'announcement',
      'title' => 'Test global announcement',
    ]);
    $globalAnnouncement->setPublishExternally(TRUE);
    $globalAnnouncement->save();

    $this->assertTrue($globalAnnouncement->publishExternally());
    $this->assertTrue($globalAnnouncement instanceof Announcement);
  }

}
