<?php

namespace Drupal\helfi_etusivu\Entity\SimpleSitemap;

use Drupal\Core\Url;
use Drupal\simple_sitemap\Entity\SimpleSitemap;

class HelfiSimpleSitemap extends SimpleSitemap {
  public function toUrl($rel = 'canonical', array $options = []) {
    if ($rel !== 'canonical') {
      return parent::toUrl($rel, $options);
    }

    $parameters = isset($options['delta']) ? ['page' => $options['delta']] : [];
    unset($options['delta']);

    if (empty($options['base_url'])) {
      /** @var \Drupal\simple_sitemap\Settings $settings */
      $settings = \Drupal::service('simple_sitemap.settings');
      $options['base_url'] = $settings->get('base_url') ?: $GLOBALS['base_url'];
    }

    // #UHF-10812 paged sitemap had wrong url due to how helfi works,
    // enabling path processing fixes this.
    // $options['path_processing'] = FALSE;

    return $this->isDefault()
      ? Url::fromRoute(
        'simple_sitemap.sitemap_default',
        $parameters,
        $options)
      : Url::fromRoute(
        'simple_sitemap.sitemap_variant',
        $parameters + ['variant' => $this->id()],
        $options);
  }
}