<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\SimpleSitemap;

use Drupal\Core\Url;
use Drupal\simple_sitemap\Entity\SimpleSitemap;

/**
 * Overrides the simple_sitemap entity.
 */
class HelfiSimpleSitemap extends SimpleSitemap {

  /**
   * {@inheritDoc}
   */
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

    // #UHF-11522: Use fi as default language when generating the sitemap main and paged urls.
    if (empty($options['language'])) {
      $options['language'] = $this->languageManager()->getLanguage('fi');
    }

    // #UHF-10812 paged sitemap had wrong url due to how helfi works.
    // Path_processing=false removes the langcode from the sitemap pagination
    // which conflicts with the sitemap.xml served from the public folder.
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
