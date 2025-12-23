<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\Feedback;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\FeedbackController;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Drupal\helfi_api_base\ServiceMap\DTO\Location;
use Drupal\helfi_api_base\ServiceMap\DTO\StreetName;
use Drupal\helfi_api_base\ServiceMap\ServiceMapInterface;
use Drupal\KernelTests\KernelTestBase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests feedback controller.
 *
 * @group helfi_etusivu
 */
class FeedbackControllerTest extends KernelTestBase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_api_base',
    'helfi_etusivu',
    'big_pipe',
    'system',
  ];

  /**
   * Tests controller without address.
   */
  public function testContentNoAddress() : void {
    $response = FeedbackController::create($this->container)->content(new Request());

    $this->assertIsArray($response['#autosuggest_form']);
    $this->assertArrayNotHasKey('feedback', $response);
    $this->assertArrayHasKey('#address_missing_message', $response);
    $message = $response["#address_missing_message"];
    $this->assertInstanceOf(TranslatableMarkup::class, $message);
    $this->assertIsString('Start by searching with your address.', (string) $message);
  }

  /**
   * Tests controller with valid address.
   */
  public function testContentWithAddress() : void {
    $mockRequest = new Request([
      'q' => 'Kotikatu 1',
    ]);

    $serviceMapMock = $this->prophesize(ServiceMapInterface::class);
    $serviceMapMock->getAddressData('Kotikatu 1')
      ->willReturn(
        new Address(
          StreetName::createFromArray(['fi' => 'Kotikatu 1']),
          new Location(60.171, 24.934, 'Point'),
        ),
      );
    $this->container->set(ServiceMapInterface::class, $serviceMapMock->reveal());
    $response = FeedbackController::create($this->container)->content($mockRequest);

    $this->assertIsArray($response['#autosuggest_form']);
    $this->assertArrayHasKey('#content', $response);
  }

}
