<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search\Form;

use Drupal\Core\Entity\ContentEntityForm;

/**
 * Form controller for the search suggestion entity.
 */
final class SuggestionForm extends ContentEntityForm {

  use SearchEntityFormTrait;

}
