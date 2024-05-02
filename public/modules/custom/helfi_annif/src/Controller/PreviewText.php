<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\helfi_annif\TextConverter\TextConverterManager;
use Http\Discovery\Exception\NotFoundException;

/**
 * Preview output of TextConverter.
 *
 * This page can be used by developers to check that the data passed to
 * the language model does not contain any unnecessary content that might
 * confuse it.
 */
class PreviewText extends ControllerBase {

  use AutowireTrait;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_annif\TextConverter\TextConverterManager $textConverter
   *   The text converter.
   */
  public function __construct(
    private readonly TextConverterManager $textConverter,
  ) {
  }

  /**
   * Renders TextController output.
   *
   * @param string $entity_type
   *   Entity type url parameter.
   * @param string $id
   *   ID url parameter.
   *
   * @return array
   *   Output.
   */
  public function content(string $entity_type, string $id) : array {
    try {
      $entity = $this->entityTypeManager()
        ->getStorage($entity_type)
        ->load($id);

      if ($entity instanceof TranslatableInterface) {
        $currentLanguage = $this->languageManager()->getCurrentLanguage()->getId();

        if ($entity->hasTranslation($currentLanguage)) {
          $entity = $entity->getTranslation($currentLanguage);
        }
      }

      if ($entity && $content = $this->textConverter->convert($entity)) {
        return [
          '#prefix' => '<pre>',
          '#plain_text' => $content,
          '#suffix' => '</pre>',
        ];
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException) {
    }

    throw new NotFoundException();
  }

}
