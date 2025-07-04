<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Tests\Kernel\EventSubscriber;

use Drupal\elasticsearch_connector\Event\AlterSettingsEvent;
use Drupal\elasticsearch_connector\Event\QueryParamsEvent;
use Drupal\helfi_etusivu\EventSubscriber\ElasticsearchEventSubscriber;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;

/**
 * Tests for ElasticsearchEventSubscriber.
 *
 * @group helfi_etusivu
 */
class ElasticsearchEventSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'elasticsearch_connector',
    'helfi_etusivu',
    'search_api',
  ];

  /**
   * Tests prepareIndices method.
   */
  public function testPrepareIndices() {
    $index = $this->prophesize(Index::class);
    $index->id()->willReturn('news');

    $event = new AlterSettingsEvent([], [], $index->reveal());

    $subscriber = new ElasticsearchEventSubscriber();
    $subscriber->prepareIndices($event);

    $this->assertArrayHasKey('analysis', $event->getSettings());
    $this->assertArrayHasKey('analyzer', $event->getSettings()['analysis']);
    $this->assertArrayHasKey('default', $event->getSettings()['analysis']['analyzer']);
    $this->assertArrayHasKey('type', $event->getSettings()['analysis']['analyzer']['default']);
    $this->assertEquals('finnish', $event->getSettings()['analysis']['analyzer']['default']['type']);

    $wrongIndex = $this->prophesize(Index::class);
    $wrongIndex->id()->willReturn('wrong_index');

    $wrongEvent = new AlterSettingsEvent([], [], $wrongIndex->reveal());
    $subscriber->prepareIndices($wrongEvent);
    $this->assertEmpty($wrongEvent->getSettings());
  }

  /**
   * Tests prepareQueryParams method.
   */
  public function testPrepareQueryParams() {
    $emptyEvent = new QueryParamsEvent('news', []);
    $subscriber = new ElasticsearchEventSubscriber();
    $subscriber->prepareQueryParams($emptyEvent);
    $this->assertEquals([], $emptyEvent->getParams());

    $event = new QueryParamsEvent('news', [
      'body' => [
        'query' => [
          'bool' => [
            'must' => [
              'query_string' => [
                'query' => 'test~',
              ],
            ],
          ],
        ],
      ],
    ]);

    $subscriber->prepareQueryParams($event);
    $this->assertEquals([
      'body' => [
        'query' => [
          'bool' => [
            'should' => [
              [
                'query_string' => [
                  'query' => 'test~',
                ],
              ],
              [
                'wildcard' => [
                  'title.keyword' => '*test*',
                ],
              ],
            ],
            'minimum_should_match' => 1,
          ],
        ],
      ],
      'index' => 'news',
    ], $event->getParams());
  }

}
