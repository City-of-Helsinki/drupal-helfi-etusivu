<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Search;

use Drupal\Core\Database\Connection;
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
    'draggableviews',
    'language',
    'content_translation',
    'diff',
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

    // draggableviews_schema() has no langcode column. It is added by
    // helfi_etusivu_update_9005(), and patches/draggableviews_language.patch
    // writes to it.
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

    ConfigurableLanguage::createFromLangcode('fi')->save();

    // The endpoint must be readable without an account.
    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $role->grantPermission('access content')->save();

    $this->config('language.types')
      ->set('negotiation.language_content.enabled', ['language-url' => 0])
      ->set('configurable', ['language_interface', 'language_content'])
      ->save();
    $this->config('language.negotiation')
      ->set('url.source', 'path_prefix')
      ->set('url.prefixes', ['en' => 'en', 'fi' => 'fi'])
      ->save();

    $this->container->get('kernel')->rebuildContainer();
  }

  /**
   * Tests that the saved drag order is respected.
   */
  public function testOrderIsRespected(): void {
    $first = $this->createSuggestion('ensimmäinen', 'fi');
    $second = $this->createSuggestion('toinen', 'fi');
    $third = $this->createSuggestion('kolmas', 'fi');

    $this->setWeight($third, 'fi', 0);
    $this->setWeight($first, 'fi', 1);
    $this->setWeight($second, 'fi', 2);

    // Suggestions with no saved weight sort last.
    $this->createSuggestion('no weight', 'fi');

    // Each language keeps its own order.
    $a = $third->addTranslation('en', [
      'suggestion' => 'aaa',
    ]);
    $a->save();

    $b = $this->createSuggestion('bbb', 'en');
    $this->setWeight($b, 'en', 0);
    $this->setWeight($a, 'en', 1);

    // The endpoint is readable by anonymous users.
    $this->assertTrue($this->container->get('current_user')->isAnonymous());

    $this->assertTerms(['kolmas', 'ensimmäinen', 'toinen', 'no weight'], 'fi');
    $this->assertTerms(['bbb', 'aaa'], 'en');
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
    $this->container->get(Connection::class)->insert('draggableviews_structure')
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
   * Asserts the endpoint returns exactly the given terms, in order.
   *
   * @param string[] $expected
   *   The expected terms.
   * @param string $langcode
   *   The language to request.
   */
  private function assertTerms(array $expected, string $langcode): void {
    $response = $this->processRequest(
      $this->getMockedRequest(sprintf('/%s/api/v1/search-suggestions', $langcode))
    );
    $this->assertEquals(200, $response->getStatusCode());

    $suggestions = json_decode((string) $response->getContent(), TRUE);
    $this->assertSame($expected, array_column($suggestions, 'term'));
  }

}
