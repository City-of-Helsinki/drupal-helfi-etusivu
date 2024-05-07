<?php

declare(strict_types=1);

namespace Drupal\helfi_annif\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\helfi_annif\TextConverter\TextConverterManager;
use Drush\Attributes\Argument;
use Drush\Attributes\Command;
use Drush\Attributes\Option;
use Drush\Attributes\Usage;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
final class PreviewCommands extends DrushCommands {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\helfi_annif\TextConverter\TextConverterManager $textConverter
   *   The text converter.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly TextConverterManager $textConverter,
  ) {
  }

  /**
   * Preview entity text conversion result.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $id
   *   The entity id.
   * @param string|null $language
   *   The entity language.
   *
   * @return int
   *   The exit code.
   */
  #[Command(name: 'helfi:preview-text')]
  #[Argument(name: 'entity_type', description: 'Entity type')]
  #[Argument(name: 'id', description: 'Entity id')]
  #[Option(name: 'language', description: 'Entity language')]
  #[Usage(name: 'drush helfi:preview-text node 123', description: 'Preview node with id 123.')]
  public function preview(string $entity_type, string $id, ?string $language = NULL) : int {
    try {
      $entity = $this->entityTypeManager
        ->getStorage($entity_type)
        ->load($id);

      if ($entity instanceof TranslatableInterface && $language) {
        if ($entity->hasTranslation($language)) {
          $entity = $entity->getTranslation($language);
        }
      }

      if ($entity && $content = $this->textConverter->convert($entity)) {
        $this->io()->text($content);

        return DrushCommands::EXIT_SUCCESS;
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException) {
    }

    return DrushCommands::EXIT_FAILURE;
  }

}
