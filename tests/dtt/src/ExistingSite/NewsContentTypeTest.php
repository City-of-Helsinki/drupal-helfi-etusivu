<?php

declare(strict_types=1);

namespace Drupal\Tests\dtt\ExistingSite;

use Drupal\Core\Session\AccountInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\helfi_api_base\Functional\ExistingSiteTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests news endpoint.
 *
 * @group dtt
 */
class NewsContentTypeTest extends ExistingSiteTestBase {

  use TestFileCreationTrait;

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
   * Metatag og:image should work with news_article.
   */
  public function testNewsArticleOgImage() : void {
    $uri = $this->getTestFiles('image')[0]->uri;

    $file = File::create([
      'uri' => $uri,
    ]);
    $file->save();
    $this->markEntityForCleanup($file);

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Custom name',
      'field_media_image' => $file->id(),
    ]);
    $media->save();
    $this->markEntityForCleanup($file);

    $node = $this->createNode([
      'type' => 'news_article',
      'status' => 1,
      'langcode' => 'fi',
      'field_main_image' => $media->id(),
    ]);

    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementAttributeContains('css', 'meta[property="og:image"]', 'content', $file->getFilename());
  }

}
