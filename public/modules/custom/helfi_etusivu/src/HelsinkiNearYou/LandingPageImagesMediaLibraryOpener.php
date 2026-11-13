<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Session\AccountInterface;
use Drupal\media_library\MediaLibraryOpenerInterface;
use Drupal\media_library\MediaLibraryState;

/**
 * Opens the media library from the Helsinki near you images form.
 *
 * The field widget opener grants access based on the entity the field
 * belongs to, but the images form only uses unsaved paragraphs to build its
 * widgets. Access is granted with the form's permission instead.
 */
final class LandingPageImagesMediaLibraryOpener implements MediaLibraryOpenerInterface {

  /**
   * The opener ID.
   */
  public const string ID = 'helfi_etusivu.media_library.opener.helsinki_near_you_images';

  /**
   * {@inheritdoc}
   */
  public function checkAccess(MediaLibraryState $state, AccountInterface $account): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'administer helsinki near you images')
      ->addCacheableDependency($state);
  }

  /**
   * {@inheritdoc}
   *
   * Same as the field widget opener: inserts the selected IDs into the
   * widget's hidden field and triggers the widget's update button.
   *
   * @see \Drupal\media_library\MediaLibraryFieldWidgetOpener::getSelectionResponse()
   *
   * @phpstan-param array<int|string> $selected_ids
   */
  public function getSelectionResponse(MediaLibraryState $state, array $selected_ids): AjaxResponse {
    $widgetId = $state->getOpenerParameters()['field_widget_id'] ?? NULL;
    if (!$widgetId) {
      throw new \InvalidArgumentException('field_widget_id parameter is missing.');
    }

    return (new AjaxResponse())
      ->addCommand(new InvokeCommand("[data-media-library-widget-value=\"$widgetId\"]", 'val', [implode(',', $selected_ids)]))
      ->addCommand(new InvokeCommand("[data-media-library-widget-update=\"$widgetId\"]", 'trigger', ['mousedown']));
  }

}
