<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\Form;

use Drupal\Core\Form\FormState;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_api_base\ServiceMap\DTO\StreetName;
use Drupal\helfi_api_base\ServiceMap\ServiceMapInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Form\SearchFormBase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for SearchFormBase.
 *
 * @group helfi_etusivu
 */
class SearchFormBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'helfi_api_base',
    'helfi_etusivu',
  ];

  /**
   * Creates a concrete instance of the abstract SearchFormBase.
   */
  private function createForm(ServiceMapInterface $serviceMap): SearchFormBase {
    return new class($serviceMap) extends SearchFormBase {

      /**
       * {@inheritdoc}
       */
      protected function getRedirectRoute(): string {
        return 'helfi_etusivu.near_you';
      }

      /**
       * {@inheritdoc}
       */
      public function getFormId(): string {
        return 'test_search_form';
      }

    };
  }

  /**
   * Tests that buildForm returns expected structure.
   */
  public function testBuildFormStructure(): void {
    $serviceMap = $this->createMock(ServiceMapInterface::class);
    $form = $this->createForm($serviceMap);

    $formState = new FormState();
    $built = $form->buildForm([], $formState);

    $this->assertArrayHasKey('home_address', $built);
    $this->assertSame('helfi_location_autocomplete', $built['home_address']['#type']);
    $this->assertArrayHasKey('submit', $built['actions']);
    $this->assertSame('submit', $built['actions']['submit']['#type']);
    $this->assertContains('novalidate', array_keys($built['#attributes']));
  }

  /**
   * Tests that an empty address triggers a validation error.
   */
  public function testValidateFormEmptyAddress(): void {
    $serviceMap = $this->createMock(ServiceMapInterface::class);
    $serviceMap->expects($this->never())->method('getAddressData');

    $form = $this->createForm($serviceMap);
    $formState = new FormState();
    $formState->setValue('home_address', '');

    $built = $form->buildForm([], $formState);
    $form->validateForm($built, $formState);

    $errors = $formState->getErrors();
    $this->assertArrayHasKey('home_address', $errors);
  }

  /**
   * Tests that an unresolvable address triggers a validation error.
   */
  public function testValidateFormUnresolvableAddress(): void {
    $serviceMap = $this->createMock(ServiceMapInterface::class);
    $serviceMap->expects($this->once())
      ->method('getAddressData')
      ->willReturn(NULL);

    $form = $this->createForm($serviceMap);
    $formState = new FormState();
    $formState->setValue('home_address', 'Nonexistent Street 99');

    $built = $form->buildForm([], $formState);
    $form->validateForm($built, $formState);

    $errors = $formState->getErrors();
    $this->assertArrayHasKey('home_address', $errors);
  }

  /**
   * Tests that a valid address passes validation.
   */
  public function testValidateFormValidAddress(): void {
    $serviceMap = $this->createMock(ServiceMapInterface::class);
    $serviceMap->expects($this->once())
      ->method('getAddressData')
      ->willReturn(new Address(
        StreetName::createFromArray(['fi' => 'Kotikatu 1']),
        Location::createFromArray(['coordinates' => [60.171, 24.934], 'type' => 'Point']),
      ));

    $form = $this->createForm($serviceMap);
    $formState = new FormState();
    $formState->setValue('home_address', 'Kotikatu 1');

    $built = $form->buildForm([], $formState);
    $form->validateForm($built, $formState);

    $this->assertEmpty($formState->getErrors());
  }

  /**
   * Tests that submitForm sets the redirect correctly.
   */
  public function testSubmitFormSetsRedirect(): void {
    $serviceMap = $this->createMock(ServiceMapInterface::class);
    $form = $this->createForm($serviceMap);

    $formState = new FormState();
    $formState->setValue('home_address', 'Kotikatu 1');

    $built = $form->buildForm([], $formState);
    $form->submitForm($built, $formState);

    $redirect = $formState->getRedirect();
    $this->assertNotNull($redirect);
    // Url object: check route name and parameters.
    $this->assertSame('helfi_etusivu.near_you', $redirect->getRouteName());
    $this->assertSame('Kotikatu 1', $redirect->getRouteParameters()['home_address']);
  }

}
