<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Search;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\helfi_etusivu\Entity\Search\Suggestion;
use Drupal\helfi_etusivu\Search\SearchSuggestionRepository;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the search suggestions JSON API.
 */
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
class SearchSuggestionApiTest extends KernelTestBase {

  use ApiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'link',
    'filter',
    'views',
    'draggableviews',
    'language',
    'content_translation',
    'big_pipe',
    'diff',
    'scheduler',
    'helfi_api_base',
    'helfi_etusivu',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('helfi_search_suggestion');
    $this->installSchema('draggableviews', ['draggableviews_structure']);
    $this->installConfig(['system', 'user', 'language']);

    // draggableviews_schema() has no langcode column upstream. In this project
    // it exists only because helfi_etusivu_update_9005() added it, and
    // patches/draggableviews_language.patch writes to it. Keep this spec
    // identical to that update hook.
    // @see helfi_etusivu_update_9005()
    $this->container->get('database')->schema()->addField(
      'draggableviews_structure',
      'langcode',
      [
        'type' => 'varchar',
        'length' => 5,
        'not null' => TRUE,
        'description' => 'language code',
      ],
    );

    foreach (['fi', 'sv'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    // The endpoint must be readable without an account.
    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $role->grantPermission('access content')->save();

    // Resolve the content language from the URL path prefix, the way the site
    // does. Etusivu is the root instance, so /fi/... reaches this route
    // directly.
    $this->config('language.types')
      ->set('negotiation.language_content.enabled', ['language-url' => 0])
      ->set('configurable', ['language_interface', 'language_content'])
      ->save();
    $this->config('language.negotiation')
      ->set('url.source', 'path_prefix')
      ->set('url.prefixes', ['en' => '', 'fi' => 'fi', 'sv' => 'sv'])
      ->save();

    $this->container->get('kernel')->rebuildContainer();
  }

  /**
   * Creates a suggestion.
   */
  private function createSuggestion(string $term, string $langcode): Suggestion {
    $suggestion = Suggestion::create([
      'suggestion' => $term,
      'langcode' => $langcode,
    ]);
    $suggestion->save();

    return $suggestion;
  }

  /**
   * Saves a drag order row, the way the drag UI would.
   */
  private function setWeight(Suggestion $suggestion, string $langcode, int $weight): void {
    $this->container->get('database')->insert('draggableviews_structure')
      ->fields([
        'view_name' => SearchSuggestionRepository::VIEW_ID,
        'view_display' => SearchSuggestionRepository::VIEW_DISPLAY_ID,
        'args' => '[]',
        'entity_id' => $suggestion->id(),
        'weight' => $weight,
        'parent' => 0,
        'langcode' => $langcode,
      ])
      ->execute();
  }

  /**
   * Requests the endpoint and returns the decoded body.
   *
   * @return array<mixed>
   *   The decoded list of suggestions.
   */
  private function getSuggestions(string $langcode): array {
    $response = $this->processRequest(
      $this->getMockedRequest(sprintf('/%s/api/v1/search-suggestions', $langcode))
    );
    $this->assertEquals(200, $response->getStatusCode());

    return Json::decode((string) $response->getContent());
  }

  /**
   * Returns just the terms, in response order.
   *
   * @return string[]
   *   The terms.
   */
  private function getTerms(string $langcode): array {
    return array_column($this->getSuggestions($langcode), 'term');
  }

  /**
   * Tests that the endpoint is readable by anonymous users.
   */
  public function testAnonymousAccess(): void {
    $this->createSuggestion('asukaspysäköinti', 'fi');

    $this->assertTrue($this->container->get('current_user')->isAnonymous());
    $this->assertEquals(['asukaspysäköinti'], $this->getTerms('fi'));
  }

  /**
   * Tests that the saved drag order is respected.
   */
  public function testOrderIsRespected(): void {
    $first = $this->createSuggestion('ensimmäinen', 'fi');
    $second = $this->createSuggestion('toinen', 'fi');
    $third = $this->createSuggestion('kolmas', 'fi');

    // Deliberately not creation order.
    $this->setWeight($third, 'fi', 0);
    $this->setWeight($first, 'fi', 1);
    $this->setWeight($second, 'fi', 2);

    $this->assertSame(['kolmas', 'ensimmäinen', 'toinen'], $this->getTerms('fi'));
  }

  /**
   * Tests that suggestions with no saved weight sort last.
   *
   * This pins the COALESCE in SearchSuggestionRepository to the view's
   * 'draggable_views_null_order: after' setting. If one changes, this fails.
   */
  public function testUnsortedItemsLandLast(): void {
    $sorted = $this->createSuggestion('järjestetty', 'fi');
    // Created first, so it would win any id based tiebreak.
    $this->setWeight($sorted, 'fi', 5);
    $this->createSuggestion('uusi', 'fi');

    $this->assertSame(['järjestetty', 'uusi'], $this->getTerms('fi'));
  }

  /**
   * Tests that each language keeps its own order.
   */
  public function testLanguageSeparation(): void {
    $a = $this->createSuggestion('aaa', 'fi');
    $b = $this->createSuggestion('bbb', 'fi');
    $this->setWeight($a, 'fi', 0);
    $this->setWeight($b, 'fi', 1);

    $c = $this->createSuggestion('ccc', 'sv');
    $d = $this->createSuggestion('ddd', 'sv');
    // Reversed relative to creation order.
    $this->setWeight($d, 'sv', 0);
    $this->setWeight($c, 'sv', 1);

    $this->assertSame(['aaa', 'bbb'], $this->getTerms('fi'));
    $this->assertSame(['ddd', 'ccc'], $this->getTerms('sv'));
  }

  /**
   * Tests that a suggestion is absent from languages it has no translation in.
   */
  public function testUntranslatedAreOmitted(): void {
    $this->createSuggestion('vain suomeksi', 'fi');

    $this->assertSame(['vain suomeksi'], $this->getTerms('fi'));
    $this->assertSame([], $this->getSuggestions('sv'));
  }

  /**
   * Tests that an empty result is a 200 rather than a 404.
   */
  public function testEmptyResult(): void {
    $this->assertSame([], $this->getSuggestions('fi'));
  }

  /**
   * Tests the payload shape.
   */
  public function testResponseShape(): void {
    $suggestion = $this->createSuggestion('asukaspysäköinti', 'fi');

    $this->assertSame([
      [
        'id' => $suggestion->uuid(),
        'term' => 'asukaspysäköinti',
      ],
    ], $this->getSuggestions('fi'));
  }

  /**
   * Tests that a reorder busts the cached response.
   *
   * The draggableviews_views_submit() handler invalidates the entity list
   * cache tag plus the two view config tags. If the response is not tagged
   * with them, a reorder would leave a stale order in Varnish.
   */
  public function testCacheMetadata(): void {
    $this->createSuggestion('asukaspysäköinti', 'fi');

    $response = $this->processRequest(
      $this->getMockedRequest('/fi/api/v1/search-suggestions')
    );
    assert($response instanceof CacheableResponseInterface);
    $tags = $response->getCacheableMetadata()->getCacheTags();

    $this->assertContains('helfi_search_suggestion_list', $tags);
    $this->assertContains('config:views.view.search_suggestions', $tags);
    $this->assertContains('config:views.view.search_suggestions.page_1', $tags);
    $this->assertContains(
      'languages:language_content',
      $response->getCacheableMetadata()->getCacheContexts(),
    );
  }

}
