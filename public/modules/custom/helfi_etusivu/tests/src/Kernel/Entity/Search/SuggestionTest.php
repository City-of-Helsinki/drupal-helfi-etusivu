<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity\Search;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\helfi_etusivu\Entity\Search\Suggestion;
use Drupal\Tests\helfi_etusivu\Kernel\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Tests the search suggestion entity.
 */
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
class SuggestionTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
    'language',
    'content_translation',
    'helfi_api_base',
    'diff',
    'text',
    'scheduler',
    'helfi_etusivu',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('helfi_search_suggestion');

    // Create a dummy user first so the actual test user is not UID 1 and
    // does not get all permissions automatically.
    $this->drupalCreateUser();
  }

  /**
   * Tests that a suggestion round-trips and exposes its term as the label.
   */
  public function testCreate(): void {
    $suggestion = Suggestion::create(['suggestion' => 'asukaspysäköinti']);
    $suggestion->save();

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('helfi_search_suggestion');
    $storage->resetCache();
    $loaded = $storage->load($suggestion->id());

    $this->assertInstanceOf(Suggestion::class, $loaded);
    $this->assertEquals('asukaspysäköinti', $loaded->getSuggestion());
    $this->assertEquals('asukaspysäköinti', $loaded->label());
  }

  /**
   * Tests that the id is an integer.
   *
   * The draggableviews_structure.entity_id column is an unsigned int, so an
   * entity with a string or uuid id key could never be dragged.
   */
  public function testIdIsInteger(): void {
    $suggestion = Suggestion::create(['suggestion' => 'tonttivuokra']);
    $suggestion->save();

    $this->assertIsNumeric($suggestion->id());
  }

  /**
   * Tests that surrounding whitespace is stripped on save.
   *
   * The term is used verbatim as the search query, so whitespace is a
   * functional bug rather than a cosmetic one.
   */
  public function testPreSaveTrimsWhitespace(): void {
    // Non-breaking space and a regular trailing space.
    $suggestion = Suggestion::create(['suggestion' => "\u{00A0}uimahalli aukioloajat "]);
    $suggestion->save();

    $this->assertEquals('uimahalli aukioloajat', $suggestion->getSuggestion());
  }

  /**
   * Tests that a suggestion can be translated independently.
   */
  public function testTranslation(): void {
    $this->installConfig(['language']);
    \Drupal::service('entity_type.manager')
      ->getStorage('configurable_language')
      ->create(['id' => 'sv', 'label' => 'Swedish'])
      ->save();

    $suggestion = Suggestion::create([
      'suggestion' => 'asukaspysäköinti',
      'langcode' => 'en',
    ]);
    $suggestion->addTranslation('sv', ['suggestion' => 'boendeparkering']);
    $suggestion->save();

    $this->assertEquals('asukaspysäköinti', $suggestion->getTranslation('en')->getSuggestion());
    $this->assertEquals('boendeparkering', $suggestion->getTranslation('sv')->getSuggestion());
  }

  /**
   * Tests that suggestions have no canonical route.
   *
   * A suggestion is nothing but its search term, so a view page would only
   * repeat what the collection and the edit form already show.
   */
  public function testNoCanonicalRoute(): void {
    $this->container->get('router.builder')->rebuild();
    $route_provider = $this->container->get('router.route_provider');

    // The edit form is the route the tabs of a single suggestion hang from.
    $this->assertNotNull($route_provider->getRouteByName('entity.helfi_search_suggestion.edit_form'));

    $this->expectException(RouteNotFoundException::class);
    $route_provider->getRouteByName('entity.helfi_search_suggestion.canonical');
  }

  /**
   * Tests that the translation overview works without a canonical route.
   *
   * Content translation only derives the translation paths for entity types
   * that have a canonical link, so the suggestion spells them out itself.
   */
  public function testTranslationOverviewRoute(): void {
    $this->installConfig(['language']);
    \Drupal::service('entity_type.manager')
      ->getStorage('configurable_language')
      ->create(['id' => 'sv', 'label' => 'Swedish'])
      ->save();
    $this->container->get('content_translation.manager')
      ->setEnabled('helfi_search_suggestion', 'helfi_search_suggestion', TRUE);
    $this->container->get('router.builder')->rebuild();

    $route = $this->container->get('router.route_provider')
      ->getRouteByName('entity.helfi_search_suggestion.content_translation_overview');

    $this->assertEquals('/admin/search/suggestions/{helfi_search_suggestion}/translations', $route->getPath());

    $suggestion = Suggestion::create(['suggestion' => 'tonttivuokra']);
    $suggestion->save();

    $route_match = new RouteMatch(
      'entity.helfi_search_suggestion.content_translation_overview',
      $route,
      ['helfi_search_suggestion' => $suggestion],
      ['helfi_search_suggestion' => $suggestion->id()],
    );

    // The access check reads the 'access_callback' that content translation
    // would have set had there been a canonical link.
    $access = $this->container->get('content_translation.overview_access')
      ->access($route_match, $this->drupalCreateUser([
        'administer search content',
        'translate any entity',
      ]), 'helfi_search_suggestion');

    $this->assertTrue($access->isAllowed());
  }

  /**
   * Tests that access requires the administer permission.
   */
  public function testAccess(): void {
    $suggestion = Suggestion::create(['suggestion' => 'tonttivuokra']);
    $suggestion->save();

    $this->assertEntityAccess($suggestion, $this->drupalCreateUser(), FALSE);
    $this->assertEntityAccess(
      $suggestion,
      $this->drupalCreateUser(['administer search content']),
      TRUE,
    );
  }

  /**
   * Asserts entity operations for the given account.
   *
   * @param \Drupal\helfi_etusivu\Entity\Search\Suggestion $suggestion
   *   The suggestion.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check against.
   * @param bool $expected
   *   Whether the operations are expected to be allowed.
   */
  private function assertEntityAccess(Suggestion $suggestion, AccountInterface $account, bool $expected): void {
    foreach (['view', 'update', 'delete'] as $operation) {
      $this->assertEquals(
        $expected,
        $suggestion->access($operation, $account),
        sprintf('Operation "%s" did not match the expectation.', $operation),
      );
    }
    $this->assertEquals(
      $expected,
      $this->container->get('entity_type.manager')
        ->getAccessControlHandler('helfi_search_suggestion')
        ->createAccess(NULL, $account),
    );
  }

}
