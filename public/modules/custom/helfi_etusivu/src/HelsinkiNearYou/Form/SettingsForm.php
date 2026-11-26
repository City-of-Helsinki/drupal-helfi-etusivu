<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Enum\LandingPageImage;
use Drupal\helfi_etusivu\HelsinkiNearYou\LandingPageImages;
use Drupal\helfi_etusivu\HelsinkiNearYou\LandingPageImagesMediaLibraryOpener;
use Drupal\helfi_etusivu\HelsinkiNearYou\ParkingZone\ResidentParkingPrice;
use Drupal\media_library\MediaLibraryState;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Configures the Helsinki near you pages.
 *
 * Each image field is built with the widget of the corresponding paragraph's
 * image field on an unsaved paragraph, so that the widget, its settings and
 * the field constraints are the same as when editing such a paragraph.
 */
final class SettingsForm extends FormBase {

  public function __construct(
    protected readonly LandingPageImages $landingPageImages,
    protected readonly ResidentParkingPrice $residentParkingPrice,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'helfi_etusivu_helsinki_near_you_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Field widgets expect the form to have parents.
    $form['#parents'] = [];
    $form['#attached']['library'][] = 'helfi_etusivu/helsinki-near-you-settings-form';

    $form['images'] = [
      '#type' => 'details',
      '#title' => $this->t('Landing page images'),
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['helsinki-near-you-settings__images'],
      ],
    ];

    foreach (LandingPageImage::cases() as $image) {
      $paragraph = $this->getParagraph($image, $form_state);
      $fieldName = $image->getFieldName();

      $form['images'][$fieldName] = $this->getWidget($image, $paragraph)
        ->form($paragraph->get($fieldName), $form, $form_state);
      $form['images'][$fieldName]['widget']['#title'] = $image->getLabel();
      $this->useOpener($form['images'][$fieldName]['widget']);
    }

    $form['resident_parking'] = [
      '#type' => 'details',
      '#title' => $this->t('Resident parking prices'),
      '#description' => $this->t('Monthly resident parking permit prices by zone in euros. To remove a zone, empty its zone field.'),
      '#open' => TRUE,
    ];
    $form['resident_parking']['resident_parking_prices'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Zone'),
        $this->t('Price'),
        $this->t('Fully electric vehicle price'),
      ],
    ];

    $rows = [];
    foreach ($this->residentParkingPrice->getAll() as $areaKey => $prices) {
      $rows[] = ['zone' => $areaKey] + $prices;
    }
    // An empty row for adding a zone.
    $rows[] = ['zone' => '', 'price' => NULL, 'electric_price' => NULL];

    foreach ($rows as $delta => $row) {
      $form['resident_parking']['resident_parking_prices'][$delta] = [
        'zone' => [
          '#type' => 'textfield',
          '#title' => $this->t('Zone'),
          '#title_display' => 'invisible',
          '#default_value' => $row['zone'],
          '#size' => 4,
          '#maxlength' => 8,
          '#wrapper_attributes' => [
            'class' => ['helsinki-near-you-settings__parking-zone-letter'],
          ],
        ],
        'price' => $this->priceElement($this->t('Price'), $row['price']),
        'electric_price' => $this->priceElement($this->t('Fully electric vehicle price'), $row['electric_price']),
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    foreach (LandingPageImage::cases() as $image) {
      $paragraph = $this->getParagraph($image, $form_state);
      $items = $paragraph->get($image->getFieldName());
      $widget = $this->getWidget($image, $paragraph);

      $widget->extractFormValues($items, $form, $form_state);
      $violations = $items->validate();

      if ($violations->count() === 0) {
        continue;
      }

      $errorCount = count($form_state->getErrors());
      $widget->flagErrors($items, $violations, $form, $form_state);

      // The media library widget only maps violations to a 'target_id'
      // element, which it doesn't have, so it drops e.g. disallowed media
      // types. Make sure such violations still fail the validation.
      if (count($form_state->getErrors()) === $errorCount) {
        $messages = [];
        foreach ($violations as $violation) {
          $messages[] = $violation->getMessage();
        }
        $form_state->setError($form['images'][$image->getFieldName()]['widget'], implode(' ', $messages));
      }
    }

    $this->validatePrices($form, $form_state);
  }

  /**
   * Validates the resident parking prices.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @phpstan-param array<string, mixed> $form
   */
  private function validatePrices(array &$form, FormStateInterface $form_state): void {
    $zones = [];

    foreach ($form_state->getValue('resident_parking_prices') ?? [] as $delta => $row) {
      $element = &$form['resident_parking']['resident_parking_prices'][$delta];
      $zone = mb_strtoupper(trim((string) $row['zone']));
      $form_state->setValue(['resident_parking_prices', $delta, 'zone'], $zone);

      if ($zone === '') {
        continue;
      }

      if (!preg_match('/^[A-Z]+$/', $zone)) {
        $form_state->setError($element['zone'], $this->t('The zone must consist of letters only.'));
      }
      elseif (isset($zones[$zone])) {
        $form_state->setError($element['zone'], $this->t('Zone %zone is listed more than once.', ['%zone' => $zone]));
      }
      $zones[$zone] = TRUE;

      foreach (['price', 'electric_price'] as $key) {
        if ($row[$key] === '' || $row[$key] === NULL) {
          $form_state->setError($element[$key], $this->t('@name field is required.', ['@name' => $element[$key]['#title']]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $ids = [];

    foreach (LandingPageImage::cases() as $image) {
      $paragraph = $this->getParagraph($image, $form_state);
      $items = $paragraph->get($image->getFieldName());
      $this->getWidget($image, $paragraph)->extractFormValues($items, $form, $form_state);

      $ids[$image->value] = $items->isEmpty() ? NULL : (int) $items->first()?->get('target_id')->getValue();
    }

    $this->landingPageImages->setMediaIds($ids);

    $prices = [];
    foreach ($form_state->getValue('resident_parking_prices') ?? [] as $row) {
      if ($row['zone'] !== '') {
        $prices[$row['zone']] = [
          'price' => $row['price'],
          'electric_price' => $row['electric_price'],
        ];
      }
    }
    $this->residentParkingPrice->setAll($prices);

    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));
  }

  /**
   * Builds a price field.
   *
   * @param \Stringable|string $title
   *   The title.
   * @param float|null $value
   *   The default value.
   *
   * @return array
   *   The form element.
   *
   * @phpstan-return array<string, mixed>
   */
  private function priceElement(\Stringable|string $title, ?float $value): array {
    return [
      '#type' => 'number',
      '#title' => $title,
      '#title_display' => 'invisible',
      '#default_value' => $value,
      '#min' => 0,
      '#step' => 0.01,
      '#field_suffix' => '€',
      '#wrapper_attributes' => [
        'class' => ['helsinki-near-you-settings__parking-zone-price'],
      ],
    ];
  }

  /**
   * Gets the unsaved paragraph that holds the image field values.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Enum\LandingPageImage $image
   *   The image.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   The paragraph.
   */
  private function getParagraph(LandingPageImage $image, FormStateInterface $form_state): ParagraphInterface {
    $key = ['helsinki_near_you_images', $image->value];

    if (!$paragraph = $form_state->get($key)) {
      $paragraph = $this->entityTypeManager
        ->getStorage('paragraph')
        ->create(['type' => $image->getParagraphType()]);
      assert($paragraph instanceof ParagraphInterface);

      if ($mediaId = $this->landingPageImages->getMediaId($image)) {
        $paragraph->set($image->getFieldName(), $mediaId);
      }
      $form_state->set($key, $paragraph);
    }

    return $paragraph;
  }

  /**
   * Makes the media library widget open the library with this form's opener.
   *
   * @param array $widget
   *   The media library widget element.
   *
   * @phpstan-param array<string, mixed> $widget
   *
   * @see \Drupal\helfi_etusivu\HelsinkiNearYou\LandingPageImagesMediaLibraryOpener
   */
  private function useOpener(array &$widget): void {
    $state = $widget['open_button']['#media_library_state'] ?? NULL;

    // The open button is not shown when no more media can be selected.
    if (!$state instanceof MediaLibraryState) {
      return;
    }

    $widget['open_button']['#media_library_state'] = MediaLibraryState::create(
      LandingPageImagesMediaLibraryOpener::ID,
      $state->getAllowedTypeIds(),
      $state->getSelectedTypeId(),
      $state->getAvailableSlots(),
      $state->getOpenerParameters(),
    );
  }

  /**
   * Gets the widget the paragraph's form display uses for the image field.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Enum\LandingPageImage $image
   *   The image.
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   *
   * @return \Drupal\Core\Field\WidgetInterface
   *   The widget.
   */
  private function getWidget(LandingPageImage $image, ParagraphInterface $paragraph): WidgetInterface {
    $widget = EntityFormDisplay::collectRenderDisplay($paragraph, 'default')
      ->getRenderer($image->getFieldName());

    if (!$widget instanceof WidgetInterface) {
      throw new \LogicException(sprintf('The %s paragraph form display has no widget for %s.', $image->getParagraphType(), $image->getFieldName()));
    }
    return $widget;
  }

}
