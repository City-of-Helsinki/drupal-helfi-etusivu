<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\LlmsTxt;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\helfi_etusivu\LlmsTxt\SettingsForm;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the llms.txt settings form.
 */
#[Group('helfi_etusivu')]
#[CoversClass(SettingsForm::class)]
class SettingsFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'helfi_api_base',
    'helfi_etusivu',
  ];

  /**
   * Tests that submitting the form persists the content to config.
   */
  public function testSubmitSavesConfig(): void {
    $form_state = new FormState();
    $form_state->setValues(['content' => '# New content']);

    $this->container
      ->get(FormBuilderInterface::class)
      ->submitForm(SettingsForm::class, $form_state);

    $this->assertEmpty($form_state->getErrors());
    $this->assertSame('# New content', $this->config('helfi_etusivu.llms_txt')->get('content'));
  }

}
