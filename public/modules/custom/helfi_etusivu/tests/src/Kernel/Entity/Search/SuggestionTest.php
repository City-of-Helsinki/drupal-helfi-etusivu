<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity\Search;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\helfi_etusivu\Entity\Search\Suggestion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the search suggestion entity access.
 */
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
class SuggestionTest extends SearchEntityTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('helfi_search_suggestion');

    $this->container->get(ContentTranslationManagerInterface::class)
      ->setEnabled('helfi_search_suggestion', 'helfi_search_suggestion', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function createTestEntity(): ContentEntityInterface {
    $suggestion = Suggestion::create(['suggestion' => 'blaa']);
    $suggestion->addTranslation('sv', ['suggestion' => 'blaa sv']);
    $suggestion->save();

    return $suggestion;
  }

}
