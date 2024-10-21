<?php

namespace Drupal\helfi_etusivu;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\simple_sitemap\Entity\SimpleSitemap;

class HelfiSimpleSitemap extends SimpleSitemap {

  public function __construct(
    array $values,
    $entity_type,
    private LanguageManagerInterface $languageManager) {
    parent::__construct($values, $entity_type);
  }

  public function toUrl($rel = 'canonical', array $options = []) {
    $parameters = isset($options['delta']) ? ['page' => $options['delta']] : [];

    if ($parameters['page']) {
      $language = $this->languageManager->getCurrentLanguage();
      $options['language'] = $language;
    }

    return parent::toUrl($rel, $options);
  }

}
