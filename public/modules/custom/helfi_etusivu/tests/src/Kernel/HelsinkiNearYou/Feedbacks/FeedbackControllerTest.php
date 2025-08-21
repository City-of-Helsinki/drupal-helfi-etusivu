<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\HelsinkiNearYou\Feedbacks;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Controller\FeedbacksController;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Address;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\Location;
use Drupal\helfi_etusivu\HelsinkiNearYou\DTO\StreetName;
use Drupal\helfi_etusivu\HelsinkiNearYou\ServiceMapInterface;
use Drupal\KernelTests\KernelTestBase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\InputBag;
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
    'helfi_etusivu',
    'system',
  ];

  /**
   * Tests controller without address.
   */
  public function testContentNoAddress() : void {
    $mockRequest = $this->createMock(Request::class);
    $queryWithoutArgs = new InputBag([]);
    $mockRequest->query = $queryWithoutArgs;

    /** @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = $this->container->get(MessengerInterface::class);
    $response = FeedbacksController::create($this->container)->content($mockRequest);

    $this->assertIsArray($response['#autosuggest_form']);
    $this->assertArrayNotHasKey('feedback', $response);
    $messages = $messenger->messagesByType(MessengerInterface::TYPE_ERROR);
    $this->assertCount(1, $messages);
    $this->assertStringStartsWith('Make sure the address is written correctly.', (string) $messages[0]);
  }

  /**
   * Tests controller with valid address.
   */
  public function testContentWithAddress() : void {
    $mockRequest = $this->createMock(Request::class);
    $queryWithoutArgs = new InputBag([
      'q' => 'Kotikatu 1',
    ]);
    $mockRequest->query = $queryWithoutArgs;

    $serviceMapMock = $this->prophesize(ServiceMapInterface::class);
    $serviceMapMock->getAddressData('Kotikatu 1')
      ->willReturn(
        new Address(
          StreetName::createFromArray(['fi' => 'Kotikatu 1']),
          new Location(60.171, 24.934, 'Point'),
        ),
      );
    $this->container->set(ServiceMapInterface::class, $serviceMapMock->reveal());
    $response = FeedbacksController::create($this->container)->content($mockRequest);

    $this->assertIsArray($response['#autosuggest_form']);
    $this->assertArrayHasKey('#content', $response);
  }

}
