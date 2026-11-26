<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\EventSubscriber;

use Drupal\helfi_etusivu\EventSubscriber\HakuvahtiSubscriptionSubscriber;
use Drupal\helfi_etusivu\Hakuvahti\NewsSearchDescriptionBuilder;
use Drupal\helfi_hakuvahti\Event\SubscriptionAlterEvent;
use Drupal\helfi_hakuvahti\HakuvahtiRequest;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the hakuvahti subscription subscriber.
 */
#[Group('helfi_etusivu')]
#[CoversClass(HakuvahtiSubscriptionSubscriber::class)]
class HakuvahtiSubscriptionSubscriberTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Creates a subscription alter event.
   */
  private function createEvent(?string $configId, string $elasticQuery): SubscriptionAlterEvent {
    return new SubscriptionAlterEvent(new HakuvahtiRequest([
      'lang' => 'sv',
      'siteId' => 'etusivu',
      'query' => '?topic=1',
      'elasticQuery' => $elasticQuery,
      'email' => 'test@example.com',
    ], 'client'), $configId);
  }

  /**
   * Tests the subscribed events.
   */
  public function testGetSubscribedEvents(): void {
    $this->assertSame(
      [SubscriptionAlterEvent::class => 'onSubscriptionAlter'],
      HakuvahtiSubscriptionSubscriber::getSubscribedEvents(),
    );
  }

  /**
   * Tests that other configs are not altered.
   */
  #[DataProvider('otherConfigProvider')]
  public function testOtherConfig(?string $configId): void {
    $builder = $this->prophesize(NewsSearchDescriptionBuilder::class);
    $builder->build(Argument::cetera())->shouldNotBeCalled();

    $event = $this->createEvent($configId, '!!!');
    $request = $event->getHakuvahtiRequest();
    (new HakuvahtiSubscriptionSubscriber($builder->reveal()))->onSubscriptionAlter($event);

    $this->assertSame($request, $event->getHakuvahtiRequest());
  }

  /**
   * Data provider for testOtherConfig().
   *
   * @return array<string, array{string|null}>
   *   The config ids.
   */
  public static function otherConfigProvider(): array {
    return [
      'no config' => [NULL],
      'default' => ['default'],
    ];
  }

  /**
   * Tests setting the description of a news archive subscription.
   */
  #[DataProvider('descriptionProvider')]
  public function testSetsDescription(string $description): void {
    $query = ['query' => ['bool' => ['must' => [['terms' => ['news_tags' => [1]]]]]]];
    $builder = $this->prophesize(NewsSearchDescriptionBuilder::class);
    $builder->build($query, 'sv')->shouldBeCalledOnce()->willReturn($description);

    $event = $this->createEvent(HakuvahtiSubscriptionSubscriber::CONFIG_ID, base64_encode((string) json_encode($query)));
    (new HakuvahtiSubscriptionSubscriber($builder->reveal()))->onSubscriptionAlter($event);

    $this->assertSame($description, $event->getHakuvahtiRequest()->searchDescription);
  }

  /**
   * Data provider for testSetsDescription().
   *
   * @return array<string, array{string}>
   *   The descriptions.
   */
  public static function descriptionProvider(): array {
    return [
      'labels' => ['Trafik'],
      'empty' => [''],
    ];
  }

  /**
   * Tests that an invalid elastic query is rejected.
   */
  public function testInvalidQuery(): void {
    $builder = $this->prophesize(NewsSearchDescriptionBuilder::class);
    $builder->build(Argument::cetera())->shouldNotBeCalled();

    $this->expectException(\InvalidArgumentException::class);
    (new HakuvahtiSubscriptionSubscriber($builder->reveal()))
      ->onSubscriptionAlter($this->createEvent(HakuvahtiSubscriptionSubscriber::CONFIG_ID, '!!!'));
  }

}
