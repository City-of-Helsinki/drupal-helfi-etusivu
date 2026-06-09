<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity\Search\Form;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\Entity\Search\Promotion;
use Drupal\helfi_etusivu\Entity\Search\PromotionType;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\helfi_etusivu\Kernel\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the promotion entity form.
 */
#[Group('helfi_etusivu')]
#[RunTestsInSeparateProcesses]
class PromotionFormTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
    'link',
    'helfi_api_base',
    'scheduler',
    'helfi_etusivu',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('helfi_search_promotion_type');
    $this->installEntitySchema('helfi_search_promotion');
    $this->installConfig(['system']);
    PromotionType::create(['id' => 'promotion', 'label' => 'Promotion'])->save();

    // Create a dummy user so the user we set up later is not UID 1.
    $this->drupalCreateUser();
  }

  /**
   * Builds the promotion form via the entity form builder.
   *
   * @phpstan-return array<string, mixed>
   */
  private function buildForm(Promotion $entity): array {
    return $this->container
      ->get(EntityFormBuilderInterface::class)
      ->getForm($entity, 'default');
  }

  /**
   * Creates a promotion form object pre-populated with an entity.
   */
  private function createFormObject(Promotion $entity): ContentEntityFormInterface {
    $formObject = $this->container->get(EntityTypeManagerInterface::class)
      ->getFormObject('helfi_search_promotion', 'default');
    $this->assertInstanceOf(ContentEntityFormInterface::class, $formObject);
    $formObject->setEntity($entity);
    return $formObject;
  }

  /**
   * Creates an unsaved promotion entity.
   *
   * @phpstan-param array<string, mixed> $values
   */
  private function createPromotion(array $values = []): Promotion {
    return Promotion::create($values + [
      'bundle' => 'promotion',
      'title' => 'Test Promotion',
      'description' => 'Test description',
      'link' => 'https://example.com',
    ]);
  }

  /**
   * Tests that the advanced + meta groups reflect the entity state.
   */
  public function testFormStructure(): void {
    $this->drupalSetUpCurrentUser(permissions: [
      'administer search promotions',
    ]);

    // New (unsaved) entity: advanced + meta groups are present, but the
    // "published" status is hidden and the changed timestamp falls back
    // to a placeholder.
    $entity = $this->createPromotion();
    $form = $this->buildForm($entity);

    $this->assertArrayHasKey('advanced', $form);
    $this->assertSame('vertical_tabs', $form['advanced']['#type']);

    $this->assertArrayHasKey('meta', $form);
    $this->assertSame('details', $form['meta']['#type']);
    $this->assertSame('advanced', $form['meta']['#group']);

    $this->assertFalse($form['meta']['published']['#access']);
    $this->assertSame('Not saved yet', (string) $form['meta']['changed']['#markup']);
    $this->assertNotEmpty($form['meta']['author']['#markup']);

    // Saved + published entity: status is visible as "Published" and the
    // changed placeholder is replaced.
    $published = $this->createPromotion(['title' => 'Published one']);
    $published->setPublished()->save();
    $form = $this->buildForm($published);

    $this->assertTrue($form['meta']['published']['#access']);
    $this->assertSame('Published', (string) $form['meta']['published']['#markup']);
    $this->assertNotSame('Not saved yet', (string) $form['meta']['changed']['#markup']);

    // Saved + unpublished entity: status is visible as "Not published".
    $unpublished = $this->createPromotion(['title' => 'Unpublished one']);
    $unpublished->setUnpublished()->save();
    $form = $this->buildForm($unpublished);

    $this->assertSame('Not published', (string) $form['meta']['published']['#markup']);
  }

  /**
   * Tests that the status widget can unpublish a multilingual promotion.
   */
  public function testNativeStatusUnpublishesMultilingualPromotion(): void {
    ConfigurableLanguage::createFromLangcode('sv')->save();
    $this->container->get(ContentTranslationManagerInterface::class)
      ->setEnabled('helfi_search_promotion', 'promotion', TRUE);

    $this->drupalSetUpCurrentUser(permissions: [
      'administer search promotions',
    ]);

    // Published promotion with two translations.
    $entity = $this->createPromotion([
      'langcode' => 'fi',
    ]);
    $entity->setPublished()->save();
    $entity->addTranslation('sv', [
      'title' => 'Test Promotion sv',
      'description' => 'Test description sv',
      'link' => 'https://example.com',
    ])->setPublished()->save();

    $reloaded = Promotion::load($entity->id());
    $this->assertCount(2, $reloaded->getTranslationLanguages());

    // Build the fi form through the form builder so content_translation's
    // alter and entity builder run, then submit with the status widget
    // unchecked while leaving the (hidden) content_translation checkbox at
    // its default.
    $formObject = $this->createFormObject($reloaded->getTranslation('fi'));
    $formState = new FormState();
    $formState->setFormObject($formObject);
    $form = $this->container->get(FormBuilderInterface::class)
      ->buildForm($formObject, $formState);

    // The redundant content_translation checkbox is hidden.
    $this->assertFalse($form['content_translation']['status']['#access']);

    $formState->setValue(['status', 'value'], FALSE);
    $formState->setValue(['content_translation', 'status'], 1);
    $formState->setValue(['content_translation', 'uid'], (int) $this->container->get('current_user')->id());
    $formState->setValue(['content_translation', 'created'], '');

    $built = $formObject->buildEntity($form, $formState);
    $this->assertFalse($built->isPublished());
  }

  /**
   * Tests that save() persists the entity, adds a status message and redirect.
   */
  public function testSaveRedirectsToCollectionAndAddsMessage(): void {
    $this->drupalSetUpCurrentUser(permissions: [
      'administer search promotions',
    ]);

    $entity = $this->createPromotion();
    $formObject = $this->createFormObject($entity);

    $formState = new FormState();
    $formState->setFormObject($formObject);

    $saved = $formObject->save([], $formState);

    $this->assertSame(SAVED_NEW, $saved);
    $this->assertNotNull($entity->id());

    $redirect = $formState->getRedirect();
    $this->assertInstanceOf(Url::class, $redirect);
    $this->assertSame($entity->toUrl('collection')->getRouteName(), $redirect->getRouteName());

    $messenger = $this->container->get(MessengerInterface::class);
    $messages = $messenger->messagesByType(MessengerInterface::TYPE_STATUS);
    $this->assertNotEmpty($messages);
    $this->assertStringContainsString('has been created', (string) reset($messages));

    // Saving again should return SAVED_UPDATED and produce an updated message.
    $messenger->deleteAll();
    $entity->set('title', 'Updated title');
    $formObject = $this->createFormObject($entity);
    $formState = new FormState();
    $formState->setFormObject($formObject);
    $saved = $formObject->save([], $formState);

    $this->assertSame(SAVED_UPDATED, $saved);
    $messages = $messenger->messagesByType(MessengerInterface::TYPE_STATUS);
    $this->assertNotEmpty($messages);
    $this->assertStringContainsString('has been updated', (string) reset($messages));
  }

}
