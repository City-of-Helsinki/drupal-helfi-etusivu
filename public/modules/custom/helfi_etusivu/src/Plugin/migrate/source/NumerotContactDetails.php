<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;

/**
 * Source plugin for contact details from the numerot.hel.fi JSON:API.
 *
 * @see \Drupal\helfi_etusivu\Entity\Search\ContactDetails
 *
 * @phpstan-type TypedValue array{
 *   value: string,
 *   type: string|null,
 *   info: string|null
 * }
 * @phpstan-type ContactRow array{
 *   id: string,
 *   language: string,
 *   name_parts: list<string>,
 *   email: string|null,
 *   phones: list<TypedValue>,
 *   addresses: list<TypedValue>,
 *   job_title: string|null,
 *   service_hours: string|null,
 *   organization_id: string|null
 * }
 */
#[MigrateSource(id: 'helfi_numerot_contact_details')]
final class NumerotContactDetails extends NumerotSourceBase {

  /**
   * We don't want to store any non-public values.
   *
   * Currently, we don't seem to even have access to these,
   * but API documents that these exist.
   */
  private const array NON_PUBLIC = ['salainen', 'ei asiakkaille'];

  /**
   * {@inheritdoc}
   */
  protected function getUrls(): array {
    return [
      'fi' => self::BASE_URL . '/fi/jsonapi/node/person?filter[status]=1&sort=drupal_internal__nid&page[limit]=50',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @return list<ContactRow>
   *   The source rows.
   */
  protected function parseResponse(\stdClass $content, string $language): array {
    $rows = [];

    foreach ((array) $content->data as $person) {
      $attributes = $person->attributes ?? new \stdClass();
      $organizationId = $person->relationships->field_ou_ref->data->id ?? NULL;

      $row = [
        'id' => $person->id,
        'name_parts' => array_values(array_filter((array) ($attributes->field_name_parts ?? []))),
        'email' => $this->text($attributes->field_email_address ?? NULL),
        'phones' => $this->typedValues($attributes->field_phone ?? [], self::NON_PUBLIC),
        'addresses' => $this->typedValues($attributes->field_address ?? []),
        'organization_id' => $this->text($organizationId),
      ];

      foreach (self::LANGUAGES as $rowLanguage) {
        $rows[] = $row + [
          'language' => $rowLanguage,
          'job_title' => $this->translatedText($attributes, 'field_title', $rowLanguage),
          'service_hours' => $this->translatedText($attributes, 'field_puhelinpalveluajat', $rowLanguage),
        ];
      }
    }

    return $rows;
  }

  /**
   * Gets a translated attribute.
   *
   * @return string|null
   *   The value.
   */
  private function translatedText(\stdClass $attributes, string $field, string $language): ?string {
    return match ($language) {
      'fi' => $this->text($attributes->{$field} ?? NULL),
      default => $this->text($attributes->{"{$field}_{$language}"} ?? NULL),
    };
  }

  /**
   * Normalizes value/type/info items and leaves out non-public items.
   *
   * @param mixed $items
   *   The attribute value.
   * @param string[] $filter
   *   Filter our these values.
   *
   * @return list<TypedValue>
   *   The items.
   */
  private function typedValues(mixed $items, array $filter = []): array {
    $values = [];

    foreach (is_array($items) ? $items : [] as $item) {
      if (!is_object($item) || !$value = $this->text($item->value ?? NULL)) {
        continue;
      }
      $info = $this->text($item->info ?? NULL);

      if ($info && array_any($filter, fn (string $value) => mb_strtolower($info) === mb_strtolower($value))) {
        continue;
      }

      $values[] = [
        'value' => $value,
        'type' => $this->text($item->type ?? NULL),
        'info' => $info,
      ];
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<key-of<ContactRow>, string>
   */
  public function fields(): array {
    return [
      'id' => 'The numerot.hel.fi API ID',
      'language' => 'The language code',
      'name_parts' => 'Name split into parts',
      'email' => 'Email address',
      'phones' => 'Phone numbers with type and info',
      'addresses' => 'Addresses with type',
      'job_title' => 'Job title in the row language',
      'service_hours' => 'Phone service hours in the row language',
      'organization_id' => 'Organization API ID',
    ];
  }

}
