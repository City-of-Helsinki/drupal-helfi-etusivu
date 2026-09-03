<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity\Search;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\helfi_etusivu\Entity\Search\Suggestion;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\helfi_etusivu\Kernel\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

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
    'content_translation',
    'diff',
    'helfi_api_base',
    'helfi_etusivu',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('helfi_search_suggestion');
    $this->installConfig(['language']);

    ConfigurableLanguage::create(['id' => 'sv', 'label' => 'Swedish'])->save();

    // Create a dummy user so the actual test user is not UID 1.
    $this->drupalCreateUser();
  }

  /**
   * Tests that the translation overview works without a canonical route.
   */
  public function testTranslationOverviewRoute(): void {
    $this->container->get(ContentTranslationManagerInterface::class)
      ->setEnabled('helfi_search_suggestion', 'helfi_search_suggestion', TRUE);

    $this->container->get(RouteBuilderInterface::class)->rebuild();

    $route = $this->container->get(RouteProviderInterface::class)
      ->getRouteByName('entity.helfi_search_suggestion.content_translation_overview');

    $this->assertEquals('/admin/search/suggestions/{helfi_search_suggestion}/translations', $route->getPath());

    $suggestion = Suggestion::create(['suggestion' => 'blaa']);
    $suggestion->save();

    $route_match = new RouteMatch(
      'entity.helfi_search_suggestion.content_translation_overview',
      $route,
      ['helfi_search_suggestion' => $suggestion],
      ['helfi_search_suggestion' => $suggestion->id()],
    );

    // Content translation automatically creates routes for entity types
    // that have a canonical link, which search suggestions don't have.
    $access = $this->container->get('content_translation.overview_access')
      ->access($route_match, $this->drupalCreateUser([
        'administer search content',
        'translate any entity',
      ]), 'helfi_search_suggestion');

    $this->assertTrue($access->isAllowed());
  }

}
