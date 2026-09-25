<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Search\Numerot;

use Drupal\helfi_etusivu\Entity\Search\ContactDetails;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api\Item\Field;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\search_api\Kernel\Processor\ProcessorTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the contact organization hierarchy processor.
 */
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
final class ContactOrganizationHierarchyTest extends ProcessorTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'diff',
    'helfi_api_base',
    'helfi_etusivu',
    'language',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp($processor = NULL): void {
    parent::setUp('helfi_contact_organization_hierarchy');

    $this->installConfig(['language']);
    $this->installEntitySchema('helfi_search_contact');
    $this->installEntitySchema('taxonomy_term');
    ConfigurableLanguage::createFromLangcode('sv')->save();

    Vocabulary::create([
      'vid' => 'numerot_organization',
      'name' => 'Organizations',
    ])->save();

    $this->index->addDatasource(\Drupal::service('search_api.plugin_helper')
      ->createDatasourcePlugin($this->index, 'entity:helfi_search_contact'));

    $field = new Field($this->index, 'organization_hierarchy');
    $field->setType('object');
    $field->setDatasourceId('entity:helfi_search_contact');
    $field->setPropertyPath('organization_hierarchy');
    $this->index->addField($field);
  }

  /**
   * Indexes the contact in the given language.
   *
   * @return array<mixed>
   *   The indexed values.
   */
  private function index(ContactDetails $contact, string $langcode): array {
    $item = $this->generateItem([
      'datasource' => 'entity:helfi_search_contact',
      'item' => $contact->getTranslation($langcode)->getTypedData(),
      'item_id' => "{$contact->id()}:$langcode",
    ]);
    $this->processor->addFieldValues($item);

    return $item->getField('organization_hierarchy')->getValues();
  }

  /**
   * Tests that the hierarchy is indexed from the root.
   */
  public function testAddFieldValues(): void {
    $division = Term::create(['vid' => 'numerot_organization', 'name' => 'Testitoimiala']);
    $division->addTranslation('sv', ['name' => 'Testsektorn']);
    $division->save();
    $unit = Term::create(['vid' => 'numerot_organization', 'name' => 'Testiyksikkö', 'parent' => $division->id()]);
    $unit->save();

    $contact = ContactDetails::create([
      'id' => 'test-contact',
      'name_parts' => ['Matti', 'Meikäläinen'],
      'organization' => $unit->id(),
    ]);
    $contact->addTranslation('sv', $contact->toArray());
    $contact->save();

    $this->assertEquals([
      ['id' => $division->id(), 'name' => 'Testitoimiala'],
      ['id' => $unit->id(), 'name' => 'Testiyksikkö'],
    ], $this->index($contact, 'en'));

    // Missing translations fall back to the default translation.
    $this->assertEquals([
      ['id' => $division->id(), 'name' => 'Testsektorn'],
      ['id' => $unit->id(), 'name' => 'Testiyksikkö'],
    ], $this->index($contact, 'sv'));

    // Contacts without an organization are left empty.
    $contact->set('organization', NULL)->save();
    $this->assertEquals([], $this->index($contact, 'en'));
  }

}
