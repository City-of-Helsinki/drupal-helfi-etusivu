<?php

declare(strict_types = 1);

namespace Drupal\Tests\dtt\ExistingSite;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\helfi_api_base\Functional\ExistingSiteTestBase;

/**
 * Tests news endpoint.
 *
 * @group dtt
 */
class NewsContentTypeTest extends ExistingSiteTestBase {

  /**
   * The administrator account.
   *
   * @var \Drupal\Core\Session\AccountInterface|null
   */
  protected ?AccountInterface $account;

  /**
   * The expected page title.
   *
   * @var string|null
   */
  protected ?string $title = '';

  /**
   * Asserts that nodes can be created through UI.
   */
  public function assertNodeCreation() : void {
    $this->title = 'Published news item ' . random_int(0, 5000);
    $this->account = $this->createUser();
    $this->account->addRole('admin');
    $this->account->save();
    $this->drupalLogin($this->account);
    $this->drupalGetWithLanguage('/node/add/news_item');
    $this->assertSession()->statusCodeEquals(200);
    $page = $this->getSession()->getPage();

    $page->selectFieldOption('langcode[0][value]', 'en');
    $page->fillField('title[0][value]', $this->title);
    $page->fillField('field_content[0][subform][field_text][0][value]', 'Test text input 1');
    $page->checkField('status[value]');
    $page->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);
    $title = $this->getSession()->getPage()->find('css', 'h1 span');
    $this->assertEquals($this->title, $title->getText());
  }

  /**
   * Asserts that news json list has the expected item.
   */
  public function assertJsonApiList() : void {
    // Sort items by changed date to make sure our newly added item is visible.
    $this->drupalGetWithLanguage('/jsonapi/node/news', options: ['query' => [
      'sort[changed][path]' => 'changed',
      'sort[changed][direction]' => 'DESC',
    ]]);
    $this->assertSession()->statusCodeEquals(200);
    $json = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    $titles = array_map(function (array $item) {
      return $item['attributes']['title'];
    }, $json['data']);
    $this->assertContains($this->title, $titles);
    $this->assertTrue($json['meta']['count'] >= 1);
  }

  /**
   * All users should have permission to see published news entities.
   */
  public function testEndpointPermissions() : void {
    $this->assertNodeCreation();
    // Test as authenticated.
    $this->assertJsonApiList();

    // Test as anonymous.
    $this->drupalLogout();
    $this->assertJsonApiList();
  }

  /**
   * Token [node:short-title] should work with news_article.
   */
  public function testNewsArticleLeadInToken() : void {
    /** @var \Drupal\Core\Utility\Token $token */
    $token = $this->container->get('token');

    $node = $this->createNode([
      'title' => 'Title',
      'type' => 'news_article',
    ]);
    $shortTitle = $token->replace('[node:short-title]', ['node' => $node]);
    $this->assertEquals('Title', $shortTitle);

    $node->set('field_short_title', 'Short title');
    $shortTitle = $token->replace('[node:short-title]', ['node' => $node]);
    $this->assertEquals('Short title', $shortTitle);
  }

}
