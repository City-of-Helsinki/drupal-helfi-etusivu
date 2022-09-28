<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_global_navigation\Functional;

use Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests overview form.
 *
 * @group helfi_global_navigation
 */
class GlobalMenuOverviewFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'language',
    'content_translation',
    'basic_auth',
    'rest',
    'helfi_global_navigation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The default route.
   *
   * @var string
   */
  private string $route = '/admin/content/integrations/global_menu';

  /**
   * The storage.
   *
   * @var \Drupal\helfi_global_navigation\Entity\Storage\GlobalMenuStorage|null
   */
  protected ?GlobalMenuStorage $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    foreach (['fi', 'sv'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    $this->config('language.negotiation')
      ->set('url.prefixes', ['en' => 'en', 'fi' => 'fi', 'sv' => 'sv'])
      ->save();
    $this->storage = $this->container->get('entity_type.manager')->getStorage('global_menu');
  }

  /**
   * Asserts form values.
   *
   * @param string $id
   *   The id.
   * @param bool $isPublished
   *   Whether the item should be published or not.
   * @param int $expectedWeight
   *   The expected item weight.
   */
  private function assertFormValues(string $id, bool $isPublished, int $expectedWeight) : void {
    $isPublished ?
      $this->assertSession()->checkboxChecked("entities[$id][status]") :
      $this->assertSession()->checkboxNotChecked("entities[$id][status]");

    $this->assertSession()->fieldValueEquals("entities[$id][weight]", $expectedWeight);
  }

  /**
   * Tests global menu list.
   */
  public function testList() : void {
    // Make sure admin permission is required to access the list page.
    $this->drupalGet($this->route);
    $this->assertSession()->statusCodeEquals(403);

    $account = $this->createUser(['administer global_menu'], admin: TRUE);
    $this->drupalLogin($account);
    $this->drupalGet($this->route);
    $this->assertSession()->statusCodeEquals(200);

    foreach (['liikenne', 'sote'] as $id) {
      $entity = $this->storage->createById($id)
        ->setUnpublished()
        ->setLabel($id . ' en');
      $entity->save();

      foreach (['fi', 'sv'] as $language) {
        $entity->addTranslation($language)
          ->setUnpublished()
          ->setLabel($id . ' ' . $language)
          ->save();
      }
    }
    $this->drupalGet($this->route);
    // Make sure entities are unpublished by default and have weight of 0.
    $this->assertFormValues('liikenne', FALSE, 0);
    $this->assertFormValues('sote', FALSE, 0);

    $weights = [
      'liikenne' => 5,
      'sote' => 6,
    ];

    $this->submitForm([
      'entities[liikenne][status]' => 1,
      'entities[sote][status]' => 1,
      'entities[liikenne][weight]' => $weights['liikenne'],
      'entities[sote][weight]' => $weights['sote'],
    ], 'Save');

    // Make sure entities can be published and the weight to be updated.
    $this->assertFormValues('liikenne', TRUE, $weights['liikenne']);
    $this->assertFormValues('sote', TRUE, $weights['sote']);

    foreach (['fi', 'sv'] as $language) {
      $this->drupalGet('/' . $language . $this->route);
      // Make sure translations are not published by default and have same
      // weight as the english version.
      $this->assertFormValues('liikenne', FALSE, $weights['liikenne']);
      $this->assertFormValues('sote', FALSE, $weights['sote']);

      $weights['liikenne'] = 3;
      $weights['sote'] = 4;

      $this->submitForm([
        'entities[liikenne][status]' => 1,
        'entities[sote][status]' => 1,
        'entities[liikenne][weight]' => $weights['liikenne'],
        'entities[sote][weight]' => $weights['sote'],
      ], 'Save');
    }

    // Make sure english version was updated when we changed the weight
    // on translation.
    $this->drupalGet($this->route);
    $this->assertFormValues('liikenne', TRUE, $weights['liikenne']);
    $this->assertFormValues('sote', TRUE, $weights['sote']);

    $this->assertEquals(['liikenne' => 3, 'sote' => 4], $weights);
  }

}
