<?php

declare(strict_types=1);

namespace Drupal\Tests\dtt\ExistingSite;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Session\AccountInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\helfi_api_base\Functional\ExistingSiteTestBase;
use Drupal\Tests\TestFileCreationTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests news endpoint.
 */
#[Group('dtt')]
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
   * Metatag og:image should work with news content.
   */
  public function testNewsOgImage() : void {
    $uri = $this->getTestFiles('image')[0]->uri;

    $file = File::create([
      'uri' => $uri,
    ]);
    $file->setTemporary();
    $file->save();

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Custom name',
      'field_media_image' => $file->id(),
    ]);
    $media->save();
    $this->markEntityForCleanup($media);

    $node = $this->createNode([
      'type' => 'news_item',
      'status' => 1,
      'langcode' => 'fi',
      'field_main_image' => $media->id(),
    ]);

    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementAttributeContains('css', 'meta[property="og:image"]', 'content', $file->getFilename());
  }

  /**
   * Tests that adding news update sets published_at field and the article:published_time metatag updates.
   */
  public function testNewsUpdate() : void {
    $node = $this->createNode([
      'type' => 'news_item',
      'status' => 1,
      'langcode' => 'fi',
    ]);

    $updateTime = new DrupalDateTime(
      'tomorrow',
      new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE)
    );

    $update = Paragraph::create([
      'type' => 'news_update',
      'field_news_update_date' => $updateTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
    ]);
    $update->save();
    $this->markEntityForCleanup($update);

    $node->set('field_news_item_updating_news', [
      'target_id' => $update->id(),
      'target_revision_id' => $update->getRevisionId(),
    ]);
    $node->save();

    // Adding news update should have updated published_at field.
    $this->assertEquals($node->get('published_at')->value, $updateTime->getTimestamp());

    $dateFormatter = $this->container->get('date.formatter');
    $newTimestamp = $dateFormatter->format($update->get('field_news_update_date')->date->getTimestamp(), 'html_datetime', 'c');

    // Article:published_time-metatag is set to the updated news creation time.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementAttributeContains('css', 'meta[property="article:published_time"]', 'content', $newTimestamp);
  }

  /**
   * The rendered "Published" date must match the article:published_time metatag.
   *
   * A news item can have multiple 'field_news_item_updating_news' updates.
   * NewsItem::preSave() copies the *latest* update's date into 'published_at',
   * which is also what the article:published_time metatag ends up using (see
   * helfi_etusivu_metatags_alter()) - so the rendered date, sourced from the
   * same 'published_at' field, must always agree with the metatag.
   */
  public function testPublishedDateMatchesMetatag() : void {
    $node = $this->createNode([
      'type' => 'news_item',
      'status' => 1,
      'langcode' => 'fi',
    ]);

    foreach (['-2 days', 'tomorrow'] as $when) {
      $updateTime = new DrupalDateTime($when, new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
      $update = Paragraph::create([
        'type' => 'news_update',
        'field_news_update_date' => $updateTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      ]);
      $update->save();
      $this->markEntityForCleanup($update);

      $node->get('field_news_item_updating_news')->appendItem([
        'target_id' => $update->id(),
        'target_revision_id' => $update->getRevisionId(),
      ]);
    }
    $node->save();

    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    $metaContent = $page->find('css', 'meta[property="article:published_time"]')->getAttribute('content');
    $publishedElement = $page->find('css', 'time.content-date__datetime--published');
    $this->assertNotNull($publishedElement, 'Rendered published date element not found.');

    // The metatag content carries seconds/offset the rendered element does
    // not, so compare them as the same point in time rather than as strings.
    $metaLocal = (new \DateTime($metaContent))->format('Y-m-d\TH:i');
    $this->assertEquals($metaLocal, $publishedElement->getAttribute('datetime'));
  }

  /**
   * The rendered "Updated" date must match the article:modified_time metatag.
   */
  public function testUpdatedDateMatchesMetatag() : void {
    // Backdate creation/publication so the later 'changed_at' bump below is
    // unambiguously "newer" and the "Updated" element actually renders (see
    // hdbt_preprocess_node(), which hides it unless published_at < changed_at).
    $node = $this->createNode([
      'type' => 'news_item',
      'status' => 1,
      'langcode' => 'fi',
      'created' => strtotime('-2 days'),
      'published_at' => strtotime('-2 days'),
    ]);

    // Simulate what ChangedAtFieldHooks::updateChangedAt() does on a normal
    // edit-form submit: bump 'changed_at' to the current request time. Saving
    // the node also bumps core 'changed' (used by article:modified_time) to
    // the same request time, so the two should always match when displayed.
    $node->set('changed_at', \Drupal::time()->getRequestTime());
    $node->save();

    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    $metaContent = $page->find('css', 'meta[property="article:modified_time"]')->getAttribute('content');
    $updatedElement = $page->find('css', 'time.content-date__datetime--updated');
    $this->assertNotNull($updatedElement, 'Rendered updated date element not found.');

    $metaLocal = (new \DateTime($metaContent))->format('Y-m-d\TH:i');
    $this->assertEquals($metaLocal, $updatedElement->getAttribute('datetime'));
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

  /**
   * Metatag og:image should work with news_article.
   */
  public function testNewsArticleOgImage() : void {
    $node = $this->createNode([
      'type' => 'news_article',
      'status' => 1,
      'langcode' => 'fi',
    ]);

    $uri = $this->getTestFiles('image')[0]->uri;

    $file = File::create([
      'uri' => $uri,
    ]);
    $file->setTemporary();
    $file->save();

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Custom name',
      'field_media_image' => $file->id(),
    ]);
    $media->save();
    $this->markEntityForCleanup($media);

    $node->set('field_main_image', $media->id());
    $node->save();

    // Media image is used.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementAttributeContains('css', 'meta[property="og:image"]', 'content', $file->getFilename());
  }

}
