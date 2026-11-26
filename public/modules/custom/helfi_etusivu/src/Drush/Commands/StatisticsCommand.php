<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Drush\Commands;

use Drupal\helfi_api_base\ServiceMap\ServiceMapInterface;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Figure;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsServiceInterface;
use Drush\Attributes\Argument;
use Drush\Attributes\Command;
use Drush\Attributes\Option;
use Drush\Attributes\Usage;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * Shows the neighbourhood statistics resolved for an address.
 */
final class StatisticsCommand extends DrushCommands {

  use AutowireTrait;

  public function __construct(
    private readonly ServiceMapInterface $serviceMap,
    private readonly StatisticsServiceInterface $statistics,
  ) {
    parent::__construct();
  }

  /**
   * Shows neighbourhood statistics for an address.
   *
   * @param string $address
   *   The address to look up.
   * @param array<string, bool> $options
   *   The command options.
   *
   * @return int
   *   The exit status.
   */
  #[Command(name: 'helfi:statistics')]
  #[Argument(name: 'address', description: 'The address, for example "Kalasatamankatu 1".')]
  #[Option(name: 'breakdown', description: 'Show the full dwellings breakdown by completion year.')]
  #[Usage(name: 'drush helfi:statistics "Kalasatamankatu 1"', description: 'Show statistics for an address.')]
  public function statistics(string $address, array $options = ['breakdown' => FALSE]): int {
    $resolved = $this->serviceMap->getAddressData($address);

    if (!$resolved) {
      $this->io()->error(sprintf('No such address: %s', $address));
      return DrushCommands::EXIT_FAILURE;
    }

    $this->io()->writeln(sprintf(
      '<info>%s</info> (%s)',
      (string) $resolved,
      (string) $resolved->location,
    ));

    try {
      $collection = $this->statistics->getStatistics($resolved);
    }
    catch (StatisticsException $e) {
      $this->io()->error(sprintf('Failed to resolve district: %s', $e->getMessage()));
      return DrushCommands::EXIT_FAILURE;
    }

    if (!$collection) {
      $this->io()->warning('Address is not inside a Helsinki statistical district.');
      return DrushCommands::EXIT_SUCCESS;
    }

    $district = $collection->district;
    $this->io()->writeln(sprintf(
      'District: <comment>%s</comment> (%s)',
      $district->getName('fi'),
      $district->code,
    ));

    foreach (['sv', 'en'] as $langcode) {
      if ($district->getName($langcode) !== $district->getName('fi')) {
        $this->io()->writeln(sprintf('  %s: %s', $langcode, $district->getName($langcode)));
      }
    }
    $this->io()->newLine();

    if (!$collection->figures) {
      $this->io()->warning('No statistics could be fetched for this district.');
      return DrushCommands::EXIT_SUCCESS;
    }

    $rows = [];
    foreach ($collection->figures as $figure) {
      $rows[] = [$figure->label, $this->format($figure), $figure->period ?? ''];

      foreach ($figure->breakdown as $child) {
        $rows[] = ['  ' . $child->label, $this->format($child), ''];

        if (!$options['breakdown']) {
          continue;
        }
        foreach ($child->breakdown as $grandChild) {
          $rows[] = ['    ' . $grandChild->label, $this->format($grandChild), ''];
        }
      }
    }
    $this->io()->table(['Figure', 'Value', 'Period'], $rows);

    return DrushCommands::EXIT_SUCCESS;
  }

  /**
   * Formats a figure for display.
   *
   * @param \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\DTO\Figure $figure
   *   The figure.
   *
   * @return string
   *   The formatted value.
   */
  private function format(Figure $figure): string {
    if (!$figure->hasValue()) {
      return '-';
    }
    $decimals = fmod($figure->value, 1.0) === 0.0 ? 0 : 1;

    return trim(sprintf(
      '%s %s',
      number_format($figure->value, $decimals, ',', ' '),
      $figure->unit ?? '',
    ));
  }

}
