<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\RoadworkData;

use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Utility\Error;
use Drupal\helfi_etusivu\HelsinkiNearYou\CoordinateConversionService;
use Drupal\helfi_etusivu\HelsinkiNearYou\Distance;
use Drupal\helfi_api_base\ServiceMap\DTO\Address;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * A lazy builder for Roadwork data block.
 */
final readonly class LazyBuilder implements TrustedCallbackInterface {

  public function __construct(
    private RoadworkDataServiceInterface $roadworkDataService,
    private PagerManagerInterface $pagerManager,
    private CoordinateConversionService $coordinateConversionService,
    #[Autowire(service: 'logger.channel.helfi_etusivu')]
    private LoggerInterface $logger,
  ) {
  }

  /**
   * A lazy-builder callback.
   *
   * @param \Drupal\helfi_api_base\ServiceMap\DTO\Address $address
   *   The address.
   * @param string $langcode
   *   The language code.
   * @param int|null $limit
   *   The number of items to show.
   * @param \Drupal\Core\Template\Attribute|null $attributes
   *   Array of attributes to pass to template.
   *
   * @return array
   *   The render array.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function build(Address $address, string $langcode, ?int $limit = NULL, ?Attribute $attributes = NULL): array {
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
      '#theme' => 'helsinki_near_you_lazy_builder_content',
    ];

    $showPager = $limit === NULL;

    // Show 10 items per page if no limit is defined.
    if ($showPager) {
      $limit = 10;
    }

    try {
      // Fetch roadwork projects within a radius of the converted coordinates.
      $data = $this->roadworkDataService->getFormattedProjectsByCoordinates(
        $address->location->lat,
        $address->location->lon,
        // 2000 meters = 2km radius.
        2000,
        $limit,
        $this->pagerManager->findPage(),
      ) ?? [];
    }
    catch (\Exception $e) {
      Error::logException($this->logger, $e);

      return $build + [
        '#title' => new TranslatableMarkup('Something went wrong while getting the results. You may try searching again.', [], ['context' => 'Helsinki near you']),
      ];
    }

    foreach ($data->items as $project) {
      $convertedCoords = $this->coordinateConversionService
        ->etrsGk25ToWgs84($project->x, $project->y);

      if (!$convertedCoords) {
        continue;
      }

      ['lat' => $lat, 'lon' => $lon] = $convertedCoords;

      $build['#content'][] = [
        '#theme' => 'helsinki_near_you_roadwork_item',
        '#title' => $project->title,
        '#uri' => $project->url,
        '#work_type' => $project->type,
        '#address' => $project->address,
        '#schedule' => $project->schedule,
        '#lat' => $lat,
        '#lon' => $lon,
        '#distance_label' => Distance::label(
          Distance::calculateDistance(
            $address->location->lat,
            $address->location->lon,
            $lat,
            $lon,
          )
        ),
        '#limit' => $limit,
        '#roadwork_attributes' => $attributes,
      ];
    }

    if ($showPager) {
      if ($data->numItems != 0) {
        $title = new PluralTranslatableMarkup(
          $data->numItems,
          '@count street and park project near address @address',
          '@count street and park projects near address @address',
          ['@address' => $address->streetName->getName($langcode)],
          ['context' => 'Helsinki near you'],
        );
      }
      else {
        $title = new TranslatableMarkup('No street and park projects were found near address @address', ['@address' => $address->streetName->getName($langcode)], ['context' => 'Helsinki near you'],);
      }

      $build['#title'] = $title;

      $this->pagerManager->createPager($data->numItems, $limit);
      $build['#content']['pager'] = [
        '#type' => 'pager',
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() : array {
    return ['build'];
  }

}
