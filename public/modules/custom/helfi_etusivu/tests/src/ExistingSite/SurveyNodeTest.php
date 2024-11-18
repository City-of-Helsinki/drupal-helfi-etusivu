<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\ExistingSite;

use Drupal\helfi_etusivu\Entity\Node\Survey;
use Drupal\Tests\helfi_api_base\Functional\ExistingSiteTestBase;

/**
 * Tests Survey node.
 *
 * @group helfi_etusivu
 */
class SurveyNodeTest extends ExistingSiteTestBase {

  /**
   * Tests 'field_publish_externally'.
   */
  public function testSurveyNodePublishExternallyField() : void {
    /** @var \Drupal\helfi_etusivu\Entity\Node\Survey $survey */
    $survey = $this->createNode([
      'type' => 'survey',
      'title' => 'Test survey',
    ]);
    $survey->save();

    $this->assertFalse($survey->publishExternally());
    $this->assertTrue($survey instanceof Survey);

    /** @var \Drupal\helfi_etusivu\Entity\Node\Survey $globalSurvey */
    $globalSurvey = $this->createNode([
      'type' => 'survey',
      'title' => 'Test global survey',
    ]);
    $globalSurvey->setPublishExternally(TRUE);
    $globalSurvey->save();

    $this->assertTrue($globalSurvey->publishExternally());
    $this->assertTrue($globalSurvey instanceof Survey);
  }

}
