<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\NewsRss;

use Drupal\rest\ResourceResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Responds to ResourceResponses.
 *
 * Core omits charset from content-type header, causing the
 * RSS values to be encoded incorrectly by some browsers.
 */
final class ContentTypeSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // Must be run after ResourceResponseSubscriber (priority 128).
      KernelEvents::RESPONSE => ['onResponse', 127],
    ];
  }

  /**
   * Responds to response event and sets the correct content-type header.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to respond to.
   */
  public function onResponse(ResponseEvent $event): void {
    $response = $event->getResponse();

    if ($event->getRequest()->getRequestFormat() === 'rss' && $response instanceof ResourceResponse) {
      $response->headers->set('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
  }

}
