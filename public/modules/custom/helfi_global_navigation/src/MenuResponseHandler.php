<?php

declare(strict_types=1);

namespace Drupal\helfi_global_navigation;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Handle menu request.
 */
class MenuResponseHandler {

  /**
   * Entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private EntityStorageInterface $entityStorage;

  /**
   * Default language id.
   *
   * @var string
   */
  private string $defaultLanguageId;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entitytype manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager
  ) {
    $this->entityStorage = $entityTypeManager->getStorage('global_menu');
    $this->defaultLanguageId = $languageManager->getDefaultLanguage()->getId();
  }

  /**
   * Get response for menu GET request.
   *
   * @param string $menu_type
   *   Menu type.
   * @param string $lang_code
   *   Language code id.
   *
   * @return array
   *   Request response data.
   */
  public function getMenuResponse(string $menu_type, string $lang_code): array {
    return $this->createMenuResponse($menu_type, $lang_code);
  }

  /**
   * Create the response.
   *
   * @param string $menu_type
   *   Menu type.
   * @param string $current_language_id
   *   Language code id.
   *
   * @return array
   *   Request response data.
   */
  public function createMenuResponse(string $menu_type, string $current_language_id): array {
    /** @var \Drupal\helfi_global_navigation\Entity\GlobalMenu[] $global_menus */
    $global_menus = $this->entityStorage->loadByProperties([
      'menu_type' => $menu_type,
      'langcode' => $this->defaultLanguageId,
    ]);

    return MenuRequest::createResponse(
      $menu_type,
      $current_language_id,
      $global_menus,
      \Drupal::time()->getCurrentTime()
    );
  }

}
