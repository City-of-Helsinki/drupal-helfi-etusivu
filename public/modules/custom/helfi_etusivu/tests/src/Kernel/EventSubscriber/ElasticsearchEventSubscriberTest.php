<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Tests\Kernel\EventSubscriber;

use Drupal\elasticsearch_connector\Event\AlterSettingsEvent;
use Drupal\elasticsearch_connector\Event\FieldMappingEvent;
use Drupal\helfi_etusivu\EventSubscriber\ElasticsearchEventSubscriber;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Item\FieldInterface;

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
    'helfi_api_base',
    'helfi_etusivu',
    'big_pipe',
    'search_api',
  ];

  /**
   * Tests prepareIndices method.
   */
  public function testPrepareIndices() {
    $index = $this->prophesize(Index::class);
    $index->id()->willReturn('news');

    $event = new AlterSettingsEvent([], [], $index->reveal());

    $subscriber = $this->container->get(ElasticsearchEventSubscriber::class);
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
   * Tests mapPromotionFields method.
   */
  public function testMapPromotionFields(): void {
    $index = $this->prophesize(Index::class);
    $index->id()->willReturn('search_promotions');

    $field = $this->prophesize(FieldInterface::class);
    $field->getIndex()->willReturn($index->reveal());
    $field->getFieldIdentifier()->willReturn('keywords');

    $event = new FieldMappingEvent($field->reveal(), []);
    $subscriber = $this->container->get(ElasticsearchEventSubscriber::class);
    $subscriber->mapPromotionFields($event);

    $param = $event->getParam();
    $this->assertEquals('text', $param['type']);
    $this->assertEquals('text', $param['fields']['fi']['type']);
    $this->assertEquals('finnish', $param['fields']['fi']['analyzer']);
    $this->assertEquals('text', $param['fields']['sv']['type']);
    $this->assertEquals('swedish', $param['fields']['sv']['analyzer']);
    $this->assertEquals('text', $param['fields']['en']['type']);
    $this->assertEquals('english', $param['fields']['en']['analyzer']);
  }

}
