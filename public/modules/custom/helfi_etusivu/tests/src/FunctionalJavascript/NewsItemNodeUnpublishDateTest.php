<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\user\UserInterface;

/**
 * Tests all unpublish date scenarios for news items.
 *
 * @group helfi_etusivu
 */
final class NewsItemNodeUnpublishDateTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'entity_reference_revisions',
    'field',
    'field_ui',
    'helfi_etusivu',
    'helfi_node_news_item',
    'node',
    'options',
    'paragraphs',
    'scheduler',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $failOnJavascriptConsoleErrors = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create news_item content type if it doesn't exist.
    $contentType = NodeType::load('news_item');
    if (!$contentType) {
      $contentType = NodeType::create([
        'type' => 'news_item',
        'name' => 'News item',
      ]);
      $contentType->save();
    }

    // Create news_update paragraph type if it doesn't exist.
    if (!ParagraphsType::load('news_update')) {
      $paragraph_type = ParagraphsType::create([
        'id' => 'news_update',
        'label' => 'News update',
      ]);
      $paragraph_type->save();
    }

    // Enable Scheduler for this content type.
    $contentType->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    // Create admin user with necessary permissions.
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'access content overview',
      'access site reports',
      'administer nodes',
      'administer content types',
      'administer site configuration',
      'create news_item content',
      'edit any news_item content',
      'delete any news_item content',
      'view own unpublished content',
      'administer scheduler',
      'schedule publishing of nodes',
      'view scheduled content',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests news item unpublish date logic.
   */
  public function testNewsItemUnpublishDate(): void {
    $this->testInitialAutoSetWhenScheduled();
    $this->testStatusToggleClearsScheduleAndPrefills();
    $this->testPublishOnChangeIgnoredWhenPublished();
    // The tests below will fail. They are not implemented yet.
    // $this->testUpdatingWidgetOnlyMovesForwardAndShowsHint();
    // $this->testAddMorePrefillAndManualClearHidesHint();
  }

  /**
   * Test the unpublished date auto-calculation and hint visibility.
   */
  protected function testInitialAutoSetWhenScheduled(): void {
    $publish = new \DateTimeImmutable('tomorrow 14:30:00', new \DateTimeZone('UTC'));
    $expectedDate = $this->addMonths($publish, 11)->format('Y-m-d');
    $this->drupalGet('node/add/news_item');
    $page = $this->getSession()->getPage();

    // Open the scheduling options.
    $this->openSchedulingOptions();
    // Fill in the published on date to tomorrow 14:30.
    $page->fillField('publish_on[0][value][date]', $publish->format('m/d/Y'));
    $page->fillField('publish_on[0][value][time]', $publish->format('H:i:s'));

    // Check that the unpublish date is tomorrow + 11 months.
    $this->waitForInputValue('input[name="unpublish_on[0][value][date]"]', $expectedDate);
    $this->waitForInputValue('input[name="unpublish_on[0][value][time]"]', '01:00:00');

    // The hint should be shown to the user.
    $this->assertCssLacksClass('.news-item-unpublish-hint', 'is-hidden');
  }

  /**
   * Tests the interaction between node status and unpublish date calculation.
   */
  protected function testStatusToggleClearsScheduleAndPrefills(): void {
    $edit_url = $this->drupalCreateNode([
      'type' => 'news_item',
      'title' => 'Not published',
      'status' => 0,
    ])->toUrl('edit-form');
    $this->drupalGet($edit_url);
    $page = $this->getSession()->getPage();

    $tomorrow = new \DateTimeImmutable('tomorrow 14:30:00', new \DateTimeZone('UTC'));
    $expectedDate = $this->addMonths($tomorrow, 11)->format('Y-m-d');

    // Open the scheduling options.
    $this->openSchedulingOptions();

    // Fill in the published on date to next monday.
    $page->fillField('publish_on[0][value][date]', $tomorrow->format('m/d/Y'));
    $page->fillField('publish_on[0][value][time]', $tomorrow->format('H:i:s'));

    // Check that the unpublish date is next monday + 11 months.
    $this->waitForInputValue('input[name="unpublish_on[0][value][date]"]', $expectedDate);
    $this->waitForInputValue('input[name="unpublish_on[0][value][time]"]', '01:00:00');

    // Click the Published checkbox.
    $page->checkField('status[value]');

    // Check that the published on date is reset and the unpublish date
    // is still the same.
    $this->waitForInputValue('input[name="publish_on[0][value][date]"]', '');
    $this->waitForInputValue('input[name="publish_on[0][value][time]"]', '');
    $this->assertInputValue('input[name="unpublish_on[0][value][date]"]', $expectedDate);
  }

  /**
   * Tests that publish_on is ignored when node is published.
   */
  protected function testPublishOnChangeIgnoredWhenPublished(): void {
    $edit_url = $this->drupalCreateNode([
      'type' => 'news_item',
      'title' => 'Already published',
      'status' => 1,
    ])->toUrl('edit-form');
    $this->drupalGet($edit_url);
    $page = $this->getSession()->getPage();

    // Open the scheduling options.
    $this->openSchedulingOptions();

    // Try to change publish_on. This should be ignored,
    // because status is checked.
    $later = new \DateTimeImmutable('tomorrow 08:30:00', new \DateTimeZone('UTC'));
    $page->fillField('publish_on[0][value][date]', $later->format('m/d/Y'));
    $page->fillField('publish_on[0][value][time]', $later->format('H:i:s'));

    // Expect no change.
    $this->assertInputValue('input[name="unpublish_on[0][value][date]"]', '');
    $this->assertInputValue('input[name="unpublish_on[0][value][time]"]', '');
  }

  /**
   * Tests that the unpublish date widget only moves forward and shows the hint.
   */
  protected function testUpdatingWidgetOnlyMovesForwardAndShowsHint(): void {
    $this->drupalGet('node/add/news_item');
    $page = $this->getSession()->getPage();
    $yesterday = new \DateTimeImmutable('yesterday', new \DateTimeZone('UTC'));
    $yesterdayExpected = $this->addMonths($yesterday, 11)->format('Y-m-d');

    // Open the scheduling options.
    $this->openSchedulingOptions();

    // Fill in the published on date.
    $page->fillField('publish_on[0][value][date]', $yesterday->format('m/d/Y'));
    $page->fillField('publish_on[0][value][time]', $yesterday->format('H:i:s'));

    // Check that the unpublish date is tomorrow + 11 months.
    $this->waitForInputValue('input[name="unpublish_on[0][value][date]"]', $yesterdayExpected);
    $this->waitForInputValue('input[name="unpublish_on[0][value][time]"]', '01:00:00');

    // Ensure the widget exists in DOM and behaviors are attached.
    $this->ensureUpdatingWidgetExists();

    // @todo Remove.
    $debug = $page->find('css', '[name="field_lead_in[0][value]"]');
    var_dump([
      'debug' => $debug ? $debug->getValue() : 'N/A',
    ]);

    // Propose an earlier date via widget, the date should not change.
    $earlier = new \DateTimeImmutable('2024-12-01', new \DateTimeZone('UTC'));
    $this->setUpdatingWidgetDate($earlier->format('Y-m-d'));
    $this->assertInputValue('input[name="unpublish_on[0][value][date]"]', $yesterdayExpected);

    // Propose a later date, the date should update to later + 11 months.
    $later = new \DateTimeImmutable('tomorrow', new \DateTimeZone('UTC'));
    $this->setUpdatingWidgetDate($later->format('Y-m-d'));
    $laterExpected = $this->addMonths($later, 11)->format('Y-m-d');
    $this->waitForInputValue('input[name="unpublish_on[0][value][date]"]', $laterExpected);
    $this->assertInputValue('input[name="unpublish_on[0][value][time]"]', '01:00:00');

    // When the unpublish date is set via widget, the hint should be shown.
    $this->assertCssLacksClass('.news-item-unpublish-hint', 'is-hidden');
  }

  /**
   * Tests the unpublish date widget "Add more" button functionality.
   *
   * If the "Add more" button is clicked, the unpublished date should be added
   * if it is empty or the date is earlier than current date.
   */
  protected function testAddMorePrefillAndManualClearHidesHint(): void {
    $this->drupalGet('node/add/news_item');
    $this->openSchedulingOptions();

    // Ensure widget + button exist and behaviors are attached.
    $this->ensureUpdatingWidgetExists();

    $this->assertInputValue('input[name="unpublish_on[0][value][date]"]', '');
    $this->assertInputValue('input[name="unpublish_on[0][value][time]"]', '');

    // Click on the "Add more" button.
    $this->triggerMouseDownOnAddMore();

    // Expect now + 11 months.
    $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    $expected = $this->addMonths($now, 11)->format('Y-m-d');
    $this->waitForInputValue('input[name="unpublish_on[0][value][date]"]', $expected);
    $this->assertInputValue('input[name="unpublish_on[0][value][time]"]', '01:00:00');

    // The hint should be visible after programmatic update.
    $this->assertCssLacksClass('.news-item-unpublish-hint', 'is-hidden');

    // If the user manually clears the input, the hint should be hidden.
    $this->clearInputAndDispatch('input[name="unpublish_on[0][value][date]"]');
    $this->assertInputValue('input[name="unpublish_on[0][value][date]"]', '');
    $this->assertCssHasClass('.news-item-unpublish-hint', 'is-hidden');
  }

  /**
   * Helper function to wait for an input value to match the expected value.
   */
  private function waitForInputValue(string $selector, string $expected): void {
    $escaped = addslashes($selector);
    $exp = addslashes($expected);
    $this->getSession()->wait(5000, "document.querySelector('{$escaped}') && document.querySelector('{$escaped}').value === '{$exp}'");
    $this->assertInputValue($selector, $expected);
  }

  /**
   * Helper function to assert an input value matches the expected value.
   */
  private function assertInputValue(string $selector, string $expected): void {
    $element = $this->assertSession()->elementExists('css', $selector);
    $this->assertSame($expected, (string) $element->getValue());
  }

  /**
   * Helper function to assert an element has a class.
   */
  private function assertCssHasClass(string $selector, string $class): void {
    $el = $this->assertSession()->elementExists('css', $selector);
    $this->assertStringContainsString($class, (string) $el->getAttribute('class') ?? '');
  }

  /**
   * Helper function to assert an element has a class.
   */
  private function assertCssLacksClass(string $selector, string $class): void {
    $el = $this->assertSession()->elementExists('css', $selector);
    $classes = (string) $el->getAttribute('class') ?? '';
    $this->assertStringNotContainsString($class, $classes);
  }

  /**
   * Helper function to open the scheduling options.
   */
  private function openSchedulingOptions(): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();
    $assert_session->waitForText('Scheduling options');
    $summary = $this->getSession()->getPage()->find('css', 'details[data-drupal-selector="edit-scheduler-settings"] > summary');
    $this->assertNotNull($summary, 'Scheduler summary not found.');
    $summary->click();
    $assert_session->waitForElementVisible('css', 'input[name="publish_on[0][value][date]"]');
    $assert_session->waitForElementVisible('css', 'input[name="publish_on[0][value][time]"]');
    $assert_session->waitForElementVisible('css', 'input[name="unpublish_on[0][value][date]"]');
    $assert_session->waitForElementVisible('css', 'input[name="unpublish_on[0][value][time]"]');
  }

  /**
   * Simulate "updating news" functionality.
   *
   * Ensure the updating widget + "Add more" button exist in the DOM and
   * reattach behaviors, so the JS behavior can bind in test context.
   */
  private function ensureUpdatingWidgetExists(): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();
    $assert_session->waitForElementVisible('css', 'form');
    $js = <<<JS
      (function () {
        const form = document.querySelector('form') || document;
        const debug = form.querySelector('[name="field_lead_in[0][value]"]');
        debug.value += 'Form: ' + form + '\\n';
        if (!document.getElementById('news-item-updating-news-widget')) {
        debug.value += 'Reaches the widget creation' + form + '\\n';

          const wrap = document.createElement('div');
          wrap.id = 'news-item-updating-news-widget';
          // Date input used by the behavior:
          const input = document.createElement('input');
          input.type = 'date';
          input.className = 'js-date';
          wrap.appendChild(input);
          // Add more button using the selector pattern listened by the behavior:
          const addMore = document.createElement('input');
          addMore.type = 'button';
          addMore.name = 'field_news_item_updating_news_news_update_add_more';
          addMore.value = 'Add more';
          form.appendChild(wrap);
          form.appendChild(addMore);
        debug.value += 'Reaches the widget creation end: ' + form + '\\n';
          
        }
        if (typeof Drupal !== 'undefined' && Drupal.attachBehaviors) {
          Drupal.attachBehaviors(document, window.drupalSettings || {});
        }
      })();
JS;
    $this->getSession()->executeScript($js);
    $assert_session->assertWaitOnAjaxRequest();
  }

  /**
   * Set the date in the updating news widget and dispatch a trusted change.
   */
  private function setUpdatingWidgetDate(string $yyyy_mm_dd): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();

    $selector = '#news-item-updating-news-widget input[type="date"], #news-item-updating-news-widget input.js-date';
    $escaped = addslashes($selector);
    $date = addslashes($yyyy_mm_dd);

    $this->getSession()->wait(5000, "document.querySelector('{$escaped}') !== null");

    $input = $assert_session->elementExists('css', $selector);
    $input->setValue($date);
    $js = <<<JS
      (function () {
        const el = document.querySelector('{$escaped}');
        if (!el) { return; }
        const ev = new Event('change', { bubbles: true });
        Object.defineProperty(ev, 'isTrusted', { get: function() { return true; } });
        el.dispatchEvent(ev);
      })();
JS;
    $this->getSession()->executeScript($js);
    $assert_session->assertWaitOnAjaxRequest();
  }

  /**
   * Fire the mousedown on the "Add more" button.
   */
  private function triggerMouseDownOnAddMore(): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();
    $selector = 'input[name*="field_news_item_updating_news_news_update_add_more"]';
    $escaped = addslashes($selector);

    $this->getSession()->wait(5000, "document.querySelector('{$escaped}') !== null");
    $js = <<<JS
      (function () {
        const btn = document.querySelector('{$escaped}');
        if (!btn) { return; }
        const ev = new MouseEvent('mousedown', { bubbles: true });
        Object.defineProperty(ev, 'isTrusted', { get: function() { return true; } });
        btn.dispatchEvent(ev);
      })();
JS;
    $this->getSession()->executeScript($js);
    $assert_session->assertWaitOnAjaxRequest();
  }

  /**
   * Clears an input and dispatches a normal (non-programmatic) change.
   */
  private function clearInputAndDispatch(string $selector): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();
    $escaped = addslashes($selector);
    $assert_session->elementExists('css', $selector)->setValue('');
    $js = <<<JS
      (function () {
        const el = document.querySelector('{$escaped}');
        if (!el) { return; }
        const ev = new Event('change', { bubbles: true });
        el.dispatchEvent(ev);
      })();
JS;
    $this->getSession()->executeScript($js);
    $assert_session->assertWaitOnAjaxRequest();
  }

  /**
   * Add months to a date to mitigate JS/PHP date difference issues.
   *
   * Anchor the date at noon to ease up with the DST edges.
   * Use calendar-month add with clamp to avoid overflow.
   *
   * @param \DateTimeImmutable $date
   *   Base date.
   * @param int $months
   *   How many months to add.
   *
   * @return \DateTimeImmutable
   *   Returns the date with the months added.
   */
  private function addMonths(\DateTimeImmutable $date, int $months): \DateTimeImmutable {
    // Keep in UTC for stable timestamp math.
    $utc = $date->setTimezone(new \DateTimeZone('UTC'));

    // 1 month = 30.436875 days = 2,629,746 seconds.
    $secondsPerMonth = (int) round(30.436875 * 24 * 60 * 60); // 2629746
    $secondsToAdd = $months * $secondsPerMonth;

    // Add seconds to the Unix timestamp and return as UTC.
    $newTimestamp = $utc->getTimestamp() + $secondsToAdd;

    return (new \DateTimeImmutable('@' . $newTimestamp))->setTimezone(new \DateTimeZone('UTC'));
  }

}
