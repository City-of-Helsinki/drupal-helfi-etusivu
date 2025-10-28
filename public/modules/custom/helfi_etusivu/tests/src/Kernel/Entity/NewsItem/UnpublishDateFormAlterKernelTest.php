<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity\NewsItem;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the unpublish date form alter functionalities.
 *
 * @group helfi_etusivu
 */
final class UnpublishDateFormAlterKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'big_pipe',
    'datetime',
    'helfi_etusivu',
    'system',
    'user',
  ];

  /**
   * The form object.
   *
   * @var \Drupal\Core\Entity\EntityFormInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityFormInterface|MockObject $formObject;

  /**
   * The node.
   *
   * @var \Drupal\node\Entity\Node|\PHPUnit\Framework\MockObject\MockObject
   */
  protected Node|MockObject $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock a node with isPublished() returning FALSE.
    $this->node = $this->getMockBuilder(Node::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->node->method('isPublished')->willReturn(FALSE);

    // Form object will return the node.
    $this->formObject = $this->getMockBuilder(EntityFormInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->formObject->method('getEntity')->willReturn($this->node);
  }

  /**
   * Tests that the form alter function adds the necessary elements to the form.
   */
  public function testFormAlterSetsAjaxAndWrappersOnNewsItem(): void {
    $form = [
      '#form_id' => 'node_news_item_form',
      // Mimic the structures used in the alter function.
      'status' => ['widget' => ['value' => ['#type' => 'checkbox']]],
      'publish_on' => ['widget' => [0 => ['value' => ['#type' => 'datetime']]]],
      'unpublish_on' => ['widget' => [0 => ['value' => ['#type' => 'datetime']]]],
    ];

    $form_state = new FormState();
    _helfi_etusivu_form_unpublish_date_alter($form, $form_state, 'node_news_item_form');

    // Test that the hint for the editor exists and has correct attributes.
    $this->assertArrayHasKey('scheduler_unpublish_hint', $form);
    $this->assertSame('container', $form['scheduler_unpublish_hint']['#type']);
    $this->assertSame(['id' => 'scheduler-unpublish-hint'], $form['scheduler_unpublish_hint']['#attributes']);

    // AJAX settings should be added.
    $this->assertArrayHasKey('#ajax', $form['status']['widget']['value']);
    $this->assertArrayHasKey('#ajax', $form['publish_on']['widget'][0]['value']);

    // Test that the unpublish widget is wrapped with div.
    $this->assertSame('<div id="scheduler-unpublish-on-widget">', $form['unpublish_on']['widget'][0]['value']['#prefix']);
    $this->assertSame('</div>', $form['unpublish_on']['widget'][0]['value']['#suffix']);
  }

  /**
   * Tests that the form alter function skips non-news item forms.
   */
  public function testFormAlterSkipsNonNewsItemForms(): void {
    $form = ['#form_id' => 'node_article_form'];
    $form_state = new FormState();

    _helfi_etusivu_form_unpublish_date_alter($form, $form_state, 'node_article_form');

    $this->assertArrayNotHasKey('scheduler_unpublish_hint', $form);
  }

  /**
   * Tests that the function does nothing if there is no user input.
   */
  public function testDoesNothingWithoutUserInput(): void {
    $form = [];
    $form_state = new FormState();

    _helfi_etusivu_set_unpublished_date($form, $form_state);

    $this->assertArrayNotHasKey('scheduler_unpublish_hint', $form);
    $this->assertNull($form_state->getValue(['unpublish_on', 0, 'value']));
  }

  /**
   * Tests that the unpublish date is set to 11 months from now.
   *
   * If the user checks the "Published" checkbox and doesn't provide
   * a published date, the unpublish date is set to 11 months from now.
   */
  public function testSetsUnpublishFromImmediatePublish(): void {
    $form = [
      '#form_id' => 'node_news_item_form',
      'unpublish_on' => ['widget' => [0 => ['value' => []]]],
    ];
    $form_state = new FormState();
    $form_state->setFormObject($this->formObject);

    // Simulate clicking the "Published" checkbox.
    $form_state->setUserInput([
      'status' => ['value' => 1],
      'publish_on' => [0 => ['value' => ['date' => '', 'time' => '']]],
      'unpublish_on' => [0 => ['value' => ['date' => '', 'time' => '']]],
    ]);

    _helfi_etusivu_set_unpublished_date($form, $form_state, 'status_widget');

    // Check that the hint container is found and has the correct attributes.
    $this->assertArrayHasKey('scheduler_unpublish_hint', $form);
    $this->assertSame('news_item_unpublish_hint', $form['scheduler_unpublish_hint']['#theme']);

    // Check that the unpublish date is set in form state.
    $storage_value = $form_state->getValue(['unpublish_on', 0, 'value']);
    $this->assertNotNull($storage_value);
    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', (string) $storage_value);

    // Test that the unpublish date is set to 11 months from now.
    $expectedDate = (new \DateTimeImmutable('now'))->add(new \DateInterval('P11M'))->format('Y-m-d');
    $stored = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $storage_value);
    $this->assertInstanceOf(DrupalDateTime::class, $stored);
    $this->assertSame($expectedDate, $stored->format('Y-m-d'));

    // Test that the unpublish date is set on the form element and that the
    // unpublished date is set in as a user input.
    $this->assertArrayHasKey('#default_value', $form['unpublish_on']['widget'][0]['value']);
    $user_input = $form_state->getUserInput();
    $this->assertNotEmpty($user_input['unpublish_on'][0]['value']['date']);
    $this->assertNotEmpty($user_input['unpublish_on'][0]['value']['time']);
  }

  /**
   * Tests that the function sets the unpublish date via "publish_on" date.
   *
   * If the user adds a "publish_on" date, the "unpublish_on" date is set
   * to 11 months from the "publish_on" date.
   */
  public function testSetsUnpublishFromScheduledPublishOn(): void {
    $form = [
      '#form_id' => 'node_news_item_form',
      'unpublish_on' => ['widget' => [0 => ['value' => []]]],
    ];
    $form_state = new FormState();
    $form_state->setFormObject($this->formObject);

    // Set the "publish on" date to 5 days from now.
    $publish_on = new \DateTimeImmutable('+5 days');
    $form_state->setUserInput([
      'status' => ['value' => 0],
      'publish_on' => [
        0 => [
          'value' => [
            'date' => $publish_on->format('Y-m-d'),
            'time' => $publish_on->format('H:i:s'),
          ],
        ],
      ],
      'unpublish_on' => [0 => ['value' => ['date' => '', 'time' => '']]],
    ]);

    _helfi_etusivu_set_unpublished_date($form, $form_state, 'publish_on_widget');

    // Check that the hint container exists.
    $this->assertArrayHasKey('scheduler_unpublish_hint', $form);

    // Check that the unpublish date is set in form state.
    $unpublish_on = $form_state->getValue(['unpublish_on', 0, 'value']);
    $this->assertNotNull($unpublish_on);
    $unpublish_on_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $unpublish_on);
    $this->assertInstanceOf(DrupalDateTime::class, $unpublish_on_date);

    // Check that the unpublish date is set to 11 months
    // from the published on date.
    $expected = $publish_on->add(new \DateInterval('P11M'));
    $this->assertSame($expected->format('Y-m-d'), $unpublish_on_date->format('Y-m-d'));
  }

  /**
   * Tests that the unpublish date updates when published date changes.
   */
  public function testUpdatesUnpublishDateWhenPublishedDateChangesToEarlier(): void {
    $form = [
      '#form_id' => 'node_news_item_form',
      'unpublish_on' => [
        'widget' => [
          0 => [
            'value' => [
              '#value' => '2030-01-01T00:00:00',
              '#default_value' => '2030-01-01T00:00:00',
            ],
          ],
        ],
      ],
    ];

    $form_state = new FormState();
    $form_state->setFormObject($this->formObject);

    // Set a published date that's earlier than the current unpublish date.
    $publish_date = new \DateTimeImmutable('2025-01-01 12:00:00');
    $form_state->setUserInput([
      'status' => ['value' => 0],
      'publish_on' => [
        0 => [
          'value' => [
            'date' => $publish_date->format('Y-m-d'),
            'time' => $publish_date->format('H:i:s'),
          ],
        ],
      ],
      'unpublish_on' => [
        0 => [
          'value' => [
            'date' => '2030-01-01',
            'time' => '00:00:00',
          ],
        ],
      ],
    ]);

    _helfi_etusivu_set_unpublished_date($form, $form_state, 'publish_on_widget');

    // The hint should be added since we're updating the unpublish date.
    $this->assertArrayHasKey('scheduler_unpublish_hint', $form);

    $unpublish_value = $form_state->getValue(['unpublish_on', 0, 'value']);
    $this->assertNotNull($unpublish_value);

    // Calculate expected unpublish date and check that it matches.
    $expected_unpublish = $publish_date->add(new \DateInterval('P11M'));
    $actual_unpublish = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $unpublish_value);
    $this->assertSame($expected_unpublish->format('Y-m-d'), $actual_unpublish->format('Y-m-d'));
  }

  /**
   * Tests behavior with a published node.
   */
  public function testPublishedNodeBehavior(): void {
    // Switch to a published node and ensure the form object returns it.
    $this->node = $this->getMockBuilder(Node::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->node->method('isPublished')->willReturn(TRUE);
    $this->formObject->method('getEntity')->willReturn($this->node);

    $form = [];
    $form_state = new FormState();
    $form_state->setFormObject($this->formObject);

    _helfi_etusivu_set_unpublished_date($form, $form_state, 'status_widget');

    // No changes for published nodes.
    $this->assertEmpty($form_state->getValues());
  }

  /**
   * Tests the AJAX response structure.
   */
  public function testAjaxResponseStructure(): void {
    $this->mockAjaxRequest();
    $form = [
      '#form_id' => 'node_news_item_form',
      'scheduler_unpublish_hint' => ['#markup' => 'Test hint'],
      'unpublish_on' => [
        'widget' => [
          0 => [
            'value' => [
              '#type' => 'datetime',
              '#name' => 'unpublish_on[0][value][date]',
            ],
          ],
        ],
      ],
    ];

    $form_state = new FormState();
    $form_state->setFormObject($this->formObject);
    $form_state->setRequestMethod('POST');
    $form_state->disableCache();

    // Test that the AJAX call returns the expected response.
    $response = _helfi_etusivu_set_unpublished_date_ajax($form, $form_state);
    $commands = $response->getCommands();
    $this->assertCount(1, $commands);
    $this->assertEquals('insert', $commands[0]['command']);
    $this->assertEquals('#scheduler-unpublish-hint', $commands[0]['selector']);
  }

  /**
   * Tests AJAX render alter functionality.
   */
  public function testAjaxRenderAlter(): void {
    // Test the status widget.
    $this->mockAjaxRequest('status[value]');

    // Run the alter hook.
    $commands = [];
    helfi_etusivu_ajax_render_alter($commands);

    // It should add 2 'invoke' commands that clear publish_on date & time.
    $this->assertCount(2, $commands);
    $this->assertEquals('invoke', $commands[0]['command']);
    $this->assertEquals('[name="publish_on[0][value][date]"]', $commands[0]['selector']);
    $this->assertEquals('invoke', $commands[1]['command']);
    $this->assertEquals('[name="publish_on[0][value][time]"]', $commands[1]['selector']);

    // Test the news update widget.
    $this->mockAjaxRequest('field_news_item_updating_news_news_update_add_more');

    // Run the alter hook.
    $commands = [];
    helfi_etusivu_ajax_render_alter($commands);

    // It should add 2 'invoke' commands that clear publish_on date & time.
    $this->assertCount(2, $commands);
    $this->assertEquals('invoke', $commands[0]['command']);
    $this->assertEquals('[name="unpublish_on[0][value][date]"]', $commands[0]['selector']);
    $this->assertEquals('invoke', $commands[1]['command']);
    $this->assertEquals('[name="unpublish_on[0][value][time]"]', $commands[1]['selector']);
  }

  /**
   * Mocks an AJAX request.
   *
   * @param string $triggering_element_name
   *   The name of the triggering element.
   */
  protected function mockAjaxRequest(string $triggering_element_name = ''): void {
    $container = $this->container;
    $renderer = $this->createMock(RendererInterface::class);
    $route_match = $this->createMock(RouteMatchInterface::class);

    // Build a request.
    $request = new Request(
      ['widget' => 'status_widget'],
      [
        'form_id' => 'node_news_item_form',
        '_triggering_element_name' => $triggering_element_name,
      ],
    );
    $request->setMethod('POST');

    // Put it on a real RequestStack.
    $request_stack = new RequestStack();
    $request_stack->push($request);

    // Set the services.
    $container->set('request_stack', $request_stack);
    $container->set('renderer', $renderer);
    $container->set('current_route_match', $route_match);
  }

}
