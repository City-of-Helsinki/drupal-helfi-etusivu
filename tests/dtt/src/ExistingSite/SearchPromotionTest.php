<?php

declare(strict_types=1);

namespace Drupal\Tests\dtt\ExistingSite;

use Drupal\Tests\helfi_api_base\Functional\ExistingSiteTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the search promotion admin UI.
 */
#[Group('dtt')]
class SearchPromotionTest extends ExistingSiteTestBase {

  /**
   * Editors should be able to create promotions via the admin UI.
   */
  public function testCreateSearchPromotion(): void {
    $title = 'DTT promotion ' . random_int(0, 100000);

    $account = $this->createUser();
    $account->addRole('admin');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGetWithLanguage('/admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->clickLink('Search promotions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/en/admin/search');

    $this->clickLink('Add promotion');
    $this->assertSession()->statusCodeEquals(200);

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', $title);
    $page->fillField('description[0][value]', 'Test promotion description.');
    $page->fillField('link[0][uri]', 'https://www.hel.fi/');
    $page->fillField('keywords[0][value]', 'dtt-test-keyword');
    $page->checkField('status[value]');
    $page->pressButton('Save');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/en/admin/search');
    $this->assertSession()->pageTextContains($title);

    $storage = \Drupal::entityTypeManager()->getStorage('helfi_search_promotion');
    $promotions = $storage->loadByProperties(['title' => $title]);
    $this->assertCount(1, $promotions);
    $this->markEntityForCleanup(reset($promotions));
  }

}
