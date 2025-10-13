<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity\NewsItem;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormState;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\KernelTests\KernelTestBase;

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
   * Tests that the form alter function adds the necessary elements to the form.
   */
  public function testFormAlterSetsAjaxAndWrappersOnNewsItem(): void {
    $form = [
      '#form_id' => 'node_news_item_form',
      // Mimic the structures used in the alter function.
      'status' => [
        'widget' => [
          'value' => [
            '#type' => 'checkbox',
          ],
        ],
      ],
      'publish_on' => [
        'widget' => [
          0 => [
            'value' => [
              '#type' => 'datetime',
            ],
          ],
        ],
      ],
      'unpublish_on' => [
        'widget' => [
          0 => [
            'value' => [
              '#type' => 'datetime',
            ],
          ],
        ],
      ],
    ];

    $form_state = new FormState();
    $form_id = 'node_news_item_form';

    _helfi_etusivu_form_unpublish_date_alter($form, $form_state, $form_id);

    // Test that the hint container is found and has the correct attributes.
    $this->assertArrayHasKey('scheduler_unpublish_hint', $form);
    $this->assertSame('container', $form['scheduler_unpublish_hint']['#type']);
    $this->assertSame(['id' => 'scheduler-unpublish-hint'], $form['scheduler_unpublish_hint']['#attributes']);

    // Check that the ajax settings are added
    // to the status and publish_on widgets.
    $this->assertArrayHasKey('#ajax', $form['status']['widget']['value']);
    $this->assertArrayHasKey('#ajax', $form['publish_on']['widget'][0]['value']);

    // Check that the unpublish widget is wrapped with
    // scheduler-unpublish-widget div.
    $this->assertSame('<div id="scheduler-unpublish-widget">', $form['unpublish_on']['widget'][0]['value']['#prefix']);
    $this->assertSame('</div>', $form['unpublish_on']['widget'][0]['value']['#suffix']);
  }

  /**
   * Tests that the form alter function skips non-news item forms.
   */
  public function testFormAlterSkipsNonNewsItemForms(): void {
    $form = [
      '#form_id' => 'node_article_form',
    ];
    $form_state = new FormState();
    $form_id = 'node_article_form';

    _helfi_etusivu_form_unpublish_date_alter($form, $form_state, $form_id);

    // Nothing added for other bundles.
    $this->assertArrayNotHasKey('scheduler_unpublish_hint', $form);
  }

  /**
   * Tests that the function does nothing if there is no user input.
   */
  public function testDoesNothingWithoutUserInput(): void {
    $form = [];
    $form_state = new FormState();

    _helfi_etusivu_set_unpublished_date($form, $form_state);

    // Nothing should be added or changed.
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
      'unpublish_on' => [
        'widget' => [
          0 => [
            'value' => [],
          ],
        ],
      ],
    ];
    $form_state = new FormState();

    // Simulate clicking the "Published" checkbox.
    $form_state->setUserInput([
      'status' => ['value' => 1],
      'publish_on' => [
        0 => [
          'value' => ['date' => '', 'time' => ''],
        ],
      ],
      'unpublish_on' => [
        0 => ['value' => ['date' => '', 'time' => '']],
      ],
    ]);

    _helfi_etusivu_set_unpublished_date($form, $form_state);

    // Check that the hint container is found and has the correct attributes.
    $this->assertArrayHasKey('scheduler_unpublish_hint', $form);
    $this->assertSame('news_item_unpublish_hint', $form['scheduler_unpublish_hint']['#theme']);

    // Check that the unpublish date is set in form state.
    $storage_value = $form_state->getValue(['unpublish_on', 0, 'value']);
    $this->assertNotNull($storage_value);
    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', (string) $storage_value);

    // Test that the unpublish date is set to 11 months from now.
    $expected = (new \DateTimeImmutable('now'))->add(new \DateInterval('P11M'));
    $stored = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $storage_value);
    $this->assertInstanceOf(DrupalDateTime::class, $stored);
    $this->assertSame($expected->format('Y-m-d'), $stored->format('Y-m-d'));

    // Check that the unpublish date is set on the form element and that the
    // unpublished date is set in user input.
    $this->assertInstanceOf(DrupalDateTime::class, $form['unpublish_on']['widget'][0]['value']['#value']);
    $this->assertInstanceOf(DrupalDateTime::class, $form['unpublish_on']['widget'][0]['value']['#default_value']);
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
      'unpublish_on' => [
        'widget' => [
          0 => [
            'value' => [],
          ],
        ],
      ],
    ];
    $form_state = new FormState();

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
      'unpublish_on' => [
        0 => ['value' => ['date' => '', 'time' => '']],
      ],
    ]);

    _helfi_etusivu_set_unpublished_date($form, $form_state);

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
   * Tests that the function does not override an existing unpublish date.
   */
  public function testNoOverrideIfUnpublishAlreadySet(): void {
    $form = [
      'unpublish_on' => [
        'widget' => [
          0 => [
            'value' => [],
          ],
        ],
      ],
    ];
    $form_state = new FormState();

    // Simulate the user providing an unpublish date.
    $form_state->setUserInput([
      'status' => ['value' => 1],
      'publish_on' => [
        0 => ['value' => ['date' => '', 'time' => '']],
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

    _helfi_etusivu_set_unpublished_date($form, $form_state);

    // The hint should not be added and unpublish date should not be changed.
    $this->assertArrayNotHasKey('scheduler_unpublish_hint', $form);
    $this->assertNull($form_state->getValue(['unpublish_on', 0, 'value']));
  }

}
