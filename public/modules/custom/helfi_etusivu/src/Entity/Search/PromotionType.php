<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Entity\Search;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Bundle entity for the helfi_search_promotion content entity.
 */
#[ConfigEntityType(
  id: 'helfi_search_promotion_type',
  label: new TranslatableMarkup('Promotion type', options: ['context' => 'Helfi search']),
  label_collection: new TranslatableMarkup('Promotion types', options: ['context' => 'Helfi search']),
  label_singular: new TranslatableMarkup('promotion type', options: ['context' => 'Helfi search']),
  label_plural: new TranslatableMarkup('promotion types', options: ['context' => 'Helfi search']),
  config_prefix: 'helfi_search_promotion_type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  admin_permission: 'administer search promotions',
  bundle_of: 'helfi_search_promotion',
  config_export: [
    'id',
    'label',
  ],
)]
final class PromotionType extends ConfigEntityBundleBase {

  /**
   * The machine name of this promotion type.
   */
  protected string $id;

  /**
   * The human-readable name of this promotion type.
   */
  protected string $label;

}
