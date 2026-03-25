<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\NewsRss;

use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * A encoder for RSS feed.
 */
class RssEncoder implements EncoderInterface {

  /**
   * {@inheritdoc}
   */
  public function encode(mixed $data, string $format, array $context = []): string {
    assert(is_string($data));
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding(string $format): bool {
    return $format === 'rss';
  }

}
