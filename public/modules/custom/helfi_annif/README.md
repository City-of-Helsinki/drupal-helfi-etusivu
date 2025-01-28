# Helfi annif integration

## Finto AI api

Keywords are generated for entities that have `field_annif_keywords` with `hook_entity_presave`. The field should be a reference to `annif_keywords` vocabulary.

See the API documentation at: [https://ai.finto.fi/v1/ui/](https://ai.finto.fi/v1/ui/).

## Suggestions across hel.fi instances

**This feature is work in progress.**

Suggested topics entities are synced to suggestions search api index. In the future, other instances communicate with etusivu so that suggested topics entity is created for their content, and they can search suggestions for their content from the shared search index.

## Text converter

The AI API accepts raw text only. Drupal content must be converted to UTF-8 encoded raw text. This is achieved with `TextConverterManager`.

```php
$raw_text = \Drupal::service(\Drupal\helfi_annif\TextConverter\TextConverterManager::class)
  ->convert(MyEntity::load(123));
```

The generic implementation provided by this module checks if the given entity has `text_converter` view mode enabled. If it is, the entity is rendered using that view mode and all HTML is stripped away.

Any irrelevant fields might degrade the suggestions, so it is important to configure the view mode to hide fields that do not contribute to the main content of the entity.

If more customization is needed, implement custom `TextConverterInterface` for your own entities.

```php
<?php

declare(strict_types=1);

namespace Drupal\my_module\TextConverter;

/**
 * Coverts my entity to text.
 */
final class MyTextConverter implements TextConverterInterface {

  /**
   * {@inheritDoc}
   */
  public function applies(EntityInterface $entity): bool {
    return $entity instanceof MyEntity;
  }

  /**
   * {@inheritDoc}
   */
  public function convert(EntityInterface $entity): string {
    assert($entity instanceof MyEntity);
    return $entity->label() . "\n" . $entity->get('field_body')->value;
  }

}

```

Text converter output can be viewed with `drush helfi:preview-text` command.
