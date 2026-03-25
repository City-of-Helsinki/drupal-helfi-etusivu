<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\NewsRss;

use Drupal\helfi_etusivu\NewsRss\DTO\RssFeed;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * A normalizer to support RSS feed.
 */
class RssNormalizer implements NormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize(mixed $data, ?string $format = NULL, array $context = []): string {
    assert($data instanceof RssFeed);

    $dom = new \DOMDocument('1.0', 'utf-8');
    $dom->formatOutput = TRUE;
    $dom->encoding = 'utf-8';

    // <rss>
    $rss = $dom->createElement('rss');
    $rss->setAttribute('version', '2.0');
    $rss->setAttribute('xmlns:dc', 'https://purl.org/dc/elements/1.1/');
    $rss->setAttribute('xml:base', $data->link);
    $dom->appendChild($rss);

    // <channel>
    $channel = $dom->createElement('channel');
    $rss->appendChild($channel);

    $this->appendTextNode($dom, $channel, 'title', $data->title, cdata: TRUE);
    $this->appendTextNode($dom, $channel, 'link', $data->link);
    $this->appendTextNode($dom, $channel, 'language', $data->language);
    $this->appendTextNode($dom, $channel, 'description', $data->description, cdata: TRUE);

    // <item> entries
    foreach ($data->items as $itemData) {
      $item = $dom->createElement('item');
      $channel->appendChild($item);

      $this->appendTextNode($dom, $item, 'title', $itemData->title, cdata: TRUE);
      $this->appendTextNode($dom, $item, 'link', $itemData->link);
      $this->appendTextNode($dom, $item, 'description', $itemData->description, cdata: TRUE);
      $this->appendTextNode($dom, $item, 'pubDate', $itemData->pubDate);

      $guid = $dom->createElement('guid', $itemData->guid);
      $guid->setAttribute('isPermaLink', 'true');
      $item->appendChild($guid);
    }

    return $dom->saveXML();
  }

  /**
   * A helper function to create a DOM element with text.
   *
   * @param \DOMDocument $dom
   *   The DOM.
   * @param \DOMElement $parent
   *   The parent DOM element.
   * @param string $tag
   *   The tag.
   * @param null|string $value
   *   The value.
   * @param bool $cdata
   *   Whether to wrap value in CDATA.
   *
   * @throws \DOMException
   */
  private function appendTextNode(\DOMDocument $dom, \DOMElement $parent, string $tag, ?string $value, bool $cdata = FALSE): void {
    $value = (string) $value;

    $element = $dom->createElement($tag);

    if ($cdata) {
      $element->appendChild($dom->createCDATASection($value));
    }
    else {
      $element->appendChild($dom->createTextNode($value));
    }
    $parent->appendChild($element);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization(mixed $data, ?string $format = NULL, array $context = []): bool {
    return $data instanceof RssFeed && $format === 'rss';
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [RssFeed::class => TRUE];
  }

}
