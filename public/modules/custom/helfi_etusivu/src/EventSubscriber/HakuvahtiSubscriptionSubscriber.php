<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\EventSubscriber;

use Drupal\helfi_etusivu\Hakuvahti\NewsSearchDescriptionBuilder;
use Drupal\helfi_hakuvahti\Event\SubscriptionAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sets the news archive search description from the Elasticsearch query.
 */
readonly class HakuvahtiSubscriptionSubscriber implements EventSubscriberInterface {

  public const string CONFIG_ID = 'news_archive';

  public function __construct(private NewsSearchDescriptionBuilder $descriptionBuilder) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SubscriptionAlterEvent::class => 'onSubscriptionAlter',
    ];
  }

  /**
   * Sets the search description of a news archive subscription.
   */
  public function onSubscriptionAlter(SubscriptionAlterEvent $event): void {
    if ($event->getConfigId() !== self::CONFIG_ID) {
      return;
    }

    $request = $event->getHakuvahtiRequest();
    $description = $this->descriptionBuilder->build($request->decodeElasticQuery(), $request->lang);
    $event->setHakuvahtiRequest($request->withSearchDescription($description));
  }

}
