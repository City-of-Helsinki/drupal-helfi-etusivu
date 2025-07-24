<?php

namespace Drupal\news_terms_trait_test\Entity;

use Drupal\helfi_etusivu\NewsTermsTrait;
use Drupal\node\Entity\Node as BaseNode;

/**
 * Bundle class to make the news terms trait available in tests.
 */
class NewsTermsTestNode extends BaseNode {
  use NewsTermsTrait;
}
