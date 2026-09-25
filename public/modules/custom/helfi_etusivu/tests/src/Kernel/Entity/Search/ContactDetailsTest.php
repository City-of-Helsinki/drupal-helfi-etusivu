<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity\Search;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\helfi_etusivu\Entity\Search\ContactDetails;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the contact details entity access.
 */
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
class ContactDetailsTest extends SearchEntityTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('helfi_search_contact');
  }

  /**
   * {@inheritdoc}
   */
  protected function createTestEntity(): ContentEntityInterface {
    $contact = ContactDetails::create([
      'id' => 'test-contact',
      'name_parts' => ['Matti', 'Meikäläinen'],
      'email' => 'matti.meikalainen@example.com',
    ]);
    $contact->save();

    return $contact;
  }

  /**
   * Tests that the entity has no public routes.
   */
  public function testNoLinkTemplates(): void {
    $this->assertEmpty($this->createTestEntity()->getEntityType()->getLinkTemplates());
  }

}
