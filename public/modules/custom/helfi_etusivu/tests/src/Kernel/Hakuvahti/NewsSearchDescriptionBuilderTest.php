<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Hakuvahti;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\helfi_etusivu\Hakuvahti\NewsSearchDescriptionBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the news search description builder with real terms.
 */
#[Group('helfi_etusivu')]
#[CoversClass(NewsSearchDescriptionBuilder::class)]
#[RunTestsInSeparateProcesses]
class NewsSearchDescriptionBuilderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'taxonomy',
    'language',
    'content_translation',
  ];

  /**
   * Term ids by name.
   *
   * @var array<string, int>
   */
  private array $tids = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['language']);

    // The weights in conf/cmi decide the translation fallback order.
    $this->config('language.entity.en')->set('weight', -8)->save();
    ConfigurableLanguage::create(['id' => 'fi', 'label' => 'Finnish', 'weight' => -10])->save();
    ConfigurableLanguage::create(['id' => 'sv', 'label' => 'Swedish', 'weight' => -9])->save();
    $this->config('system.site')->set('default_langcode', 'fi')->save();

    foreach (['news_tags', 'news_neighbourhoods', 'news_group'] as $vid) {
      Vocabulary::create(['vid' => $vid, 'name' => $vid])->save();
      $this->container->get('content_translation.manager')->setEnabled('taxonomy_term', $vid, TRUE);
    }

    $this->createTerm('traffic', 'news_tags', ['fi' => 'Liikenne', 'sv' => 'Trafik', 'en' => 'Traffic']);
    $this->createTerm('kallio', 'news_neighbourhoods', ['fi' => 'Kallio', 'sv' => 'Berghäll']);
    $this->createTerm('families', 'news_group', ['fi' => 'Lapsiperheet']);
    $this->createTerm('unpublished', 'news_tags', ['fi' => 'Piilotettu'], FALSE);
    $this->createTerm('sv_unpublished', 'news_tags', ['fi' => 'Kulttuuri', 'sv' => 'Kultur'], TRUE, ['sv']);
  }

  /**
   * Creates a term with translations.
   *
   * @param string $key
   *   The key in $this->tids.
   * @param string $vid
   *   The vocabulary.
   * @param array<string, string> $names
   *   Names by langcode, the first one is the original language.
   * @param bool $published
   *   Whether the term is published.
   * @param list<string> $unpublishedTranslations
   *   Langcodes of unpublished translations.
   */
  private function createTerm(string $key, string $vid, array $names, bool $published = TRUE, array $unpublishedTranslations = []): void {
    $langcode = (string) array_key_first($names);
    $term = Term::create([
      'vid' => $vid,
      'name' => $names[$langcode],
      'langcode' => $langcode,
      'status' => $published,
    ]);
    foreach (array_slice($names, 1, preserve_keys: TRUE) as $translationLangcode => $name) {
      $term->addTranslation($translationLangcode, [
        'name' => $name,
        'status' => !in_array($translationLangcode, $unpublishedTranslations, TRUE),
      ]);
    }
    $term->save();
    $this->tids[$key] = (int) $term->id();
  }

  /**
   * Gets the builder.
   */
  private function getBuilder(): NewsSearchDescriptionBuilder {
    return new NewsSearchDescriptionBuilder(
      $this->container->get(EntityTypeManagerInterface::class),
      $this->container->get(EntityRepositoryInterface::class),
    );
  }

  /**
   * Data provider for testLabels().
   *
   * @return array<string, array{string, string}>
   *   The langcode and the expected description.
   */
  public static function labelsProvider(): array {
    return [
      'fi' => ['fi', 'Liikenne, Kulttuuri, Kallio, Lapsiperheet'],
      'sv falls back to published fi' => ['sv', 'Trafik, Kulttuuri, Berghäll, Lapsiperheet'],
      'en falls back to fi before sv' => ['en', 'Traffic, Kulttuuri, Kallio, Lapsiperheet'],
    ];
  }

  /**
   * Tests labels per language.
   */
  #[DataProvider('labelsProvider')]
  public function testLabels(string $langcode, string $expected): void {
    $query = [
      'query' => [
        'bool' => [
          'must' => [
            ['terms' => ['news_groups' => [$this->tids['families']]]],
            ['terms' => ['neighbourhoods' => [$this->tids['kallio']]]],
            [
              'terms' => [
                'news_tags' => [
                  $this->tids['traffic'],
                  $this->tids['unpublished'],
                  $this->tids['sv_unpublished'],
                  $this->tids['kallio'],
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    $this->assertSame($expected, $this->getBuilder()->build($query, $langcode));
  }

}
