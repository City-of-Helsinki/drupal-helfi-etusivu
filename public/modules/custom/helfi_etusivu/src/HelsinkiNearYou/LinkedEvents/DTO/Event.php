<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\Client;

/**
 * A DTO to store Event item.
 */
final readonly class Event {

  public function __construct(
    public string $title,
    public bool $isFree,
    public bool $isRemote,
    public bool $registrationRequired,
    public ?Tag $category,
    public ?Image $image,
    public Url $uri,
    public string $location,
    public ?\DateTime $enrolmentStartDate,
    public ?\DateTime $enrolmentEndDate,
    public ?\DateTime $startDate,
    public ?\DateTime $endDate,
    public bool $isMultiDate,
    public array $tags,
  ) {
  }

  /**
   * Constructs a location string based on given location data.
   *
   * @param string $langcode
   *   The language code.
   * @param array $location
   *   The location data.
   *
   * @return string
   *   The location string.
   */
  private static function getLocationString(string $langcode, array $location) : string {
    $locationString = '';
    $hasName = $location['name'][$langcode] ?? NULL;
    $hasAddress = $location['street_address'][$langcode] ?? NULL;

    if ($hasName) {
      $locationString .= $hasName;
    }

    if ($hasAddress) {
      $hasName ? $locationString .= ', ' . $hasAddress : $locationString .= $hasAddress;
    }

    return $locationString;
  }

  /**
   * Parse the event category for given data.
   *
   * @param array $data
   *   The data.
   *
   * @return \Drupal\helfi_etusivu\HelsinkiNearYou\LinkedEvents\DTO\Tag|null
   *   The category tag.
   */
  private static function getCategory(array $data): ?Tag {
    if (!isset($data['type_id']) || $data['type_id'] === 'Volunteering') {
      return NULL;
    }

    if ($data['type_id'] === 'Course') {
      return new Tag(
        new TranslatableMarkup('Hobby', options: ['context' => 'Event search: hobby tag']),
        'color',
        'gold',
      );
    }
    return new Tag(
      new TranslatableMarkup('Event', options: ['context' => 'Event search: event tag']),
      'color',
      'fog-medium-light',
    );
  }

  /**
   * Constructs a new instance for given data.
   *
   * @param string $langcode
   *   The langcode.
   * @param array $data
   *   The data.
   *
   * @return self
   *   The self.
   */
  public static function createFromArray(string $langcode, array $data): self {
    $item = [
      'title' => $data['name'][$langcode] ?? $data['name']['fi'],
      'category' => self::getCategory($data),
      'isFree' => array_any($data['offers'] ?? [], function (array $offer) : bool {
        return !empty($offer['is_free']);
      }),
      'tags' => [],
      'isRemote' => $data['location']['id'] === 'helsinki:internet',
      'enrolmentStartDate' => NULL,
      'enrolmentEndDate' => NULL,
      'startDate' => NULL,
      'endDate' => NULL,
      'isMultiDate' => FALSE,
      'image' => NULL,
      'registrationRequired' => array_any($data['offers'] ?? [], function (array $offer) use ($langcode) : bool {
        return !empty($offer['info_url'][$langcode]);
      }),
    ];

    if ($image = array_first($data['images'])) {
      $item['image'] = Image::createFromArray($image);
    }

    $type = match ($langcode) {
      'fi' => 'tapahtumat',
      'sv' => 'kurser',
      default => 'events',
    };

    $item += [
      'location' => $item['isRemote'] ? 'Internet' : self::getLocationString($langcode, $data['location']),
      'uri' => Url::fromUri(sprintf('%s/%s/%s/%s', Client::BASE_URL, $langcode, $type, $data['id'])),
    ];

    if ($data['type_id'] === 'Course') {
      $type = match ($langcode) {
        'fi' => 'kurssit',
        'sv' => 'kurser',
        default => 'courses',
      };

      $item['uri'] = Url::fromUri(sprintf('%s/%s/%s/%s', Client::HOBBIES_BASE_URL, $langcode, $type, $data['id']));
    }

    if ($item['isRemote']) {
      $item['tags'][] = new Tag(
        new TranslatableMarkup('Remote participation', options: ['context' => 'Label for remote events']),
        'color',
        'silver',
      );
    }

    if ($item['isFree']) {
      $item['tags'][] = new Tag(
        new TranslatableMarkup('Free', options: ['context' => 'Label for free events']),
        'color',
        'silver',
      );
    }

    $timeProps = [
      'enrolmentStartDate' => 'enrolment_start_time',
      'enrolmentEndDate' => 'enrolment_end_time',
      'startDate' => 'start_time',
      'endDate' => 'end_time',
    ];

    foreach ($timeProps as $prop => $key) {
      if ($data[$key] === NULL) {
        continue;
      }
      $item[$prop] = new \DateTime($data[$key]);
    }

    if ($item['endDate']) {
      $item['isMultiDate'] = $item['endDate']->format('d.m.Y') !== $item['startDate']->format('d.m.Y');
    }

    return new self(...$item);
  }

}
