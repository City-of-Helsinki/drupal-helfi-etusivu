<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Enum;

/**
 * Enum class for internal search links.
 *
 * For now this class is just a "stupid" enum class with a list of links.
 * This should probably be reworked into a more robust solution in the future.
 */
enum InternalSearchLink {
  case HealthStations;
  case ChildHealthStations;
  case Schools;
  case PlaygroundsFamilyHouses;
  case Daycares;
  case PlowingSchedules;
  case NewsArchive;

  /**
   * Gets the translated link for given language.
   *
   * @param string $langcode
   *   The langcode to get link for.
   *
   * @return string
   *   The translated link.
   */
  public function getLinkTranslation(string $langcode): string {
    $translations = $this->getLinkTranslations();

    return $translations[$langcode] ?? $translations['en'];
  }

  /**
   * Return array of link translations.
   *
   * @return array
   *   Array of link translations.
   */
  public function getLinkTranslations() : array {
    return match ($this) {
      InternalSearchLink::HealthStations => [
        'fi' => 'https://www.hel.fi/fi/sosiaali-ja-terveyspalvelut/terveydenhoito/terveysasemat/etsi-oma-terveysasemasi',
        'sv' => 'https://www.hel.fi/sv/social-och-halsovardstjanster/halsovard/halsostationer/sok-din-egen-halsostation',
        'en' => 'https://www.hel.fi/en/health-and-social-services/health-care/health-stations/find-your-health-station',
      ],
      InternalSearchLink::ChildHealthStations => [
        'fi' => 'https://www.hel.fi/fi/sosiaali-ja-terveyspalvelut/lasten-ja-perheiden-palvelut/aitiys-ja-lastenneuvolat',
        'sv' => 'https://www.hel.fi/sv/social-och-halsovardstjanster/tjanster-for-barn-och-familjer/modra-och-barnradgivningarna',
        'en' => 'https://www.hel.fi/en/health-and-social-services/child-and-family-services/maternity-and-child-health-clinics',
      ],
      InternalSearchLink::Schools => [
        'fi' => 'https://www.hel.fi/fi/kasvatus-ja-koulutus/perusopetus/peruskoulut',
        'sv' => 'https://www.hel.fi/sv/fostran-och-utbildning/grundlaggande-utbildning/grundskolor',
        'en' => 'https://www.hel.fi/en/childhood-and-education/basic-education/comprehensive-schools',
      ],
      InternalSearchLink::PlaygroundsFamilyHouses => [
        'fi' => 'https://www.hel.fi/fi/kasvatus-ja-koulutus/leikkipuistot/leikkipuistot-ja-perhetalot',
        'sv' => 'https://www.hel.fi/sv/fostran-och-utbildning/lekparker/sok-lekparker-och-familjehus',
        'en' => 'https://www.hel.fi/en/childhood-and-education/playgrounds/find-playgrounds-and-family-houses',
      ],
      InternalSearchLink::Daycares => [
        'fi' => 'https://www.hel.fi/fi/kasvatus-ja-koulutus/varhaiskasvatus/varhaiskasvatus-paivakodissa/etsi-kunnallisia-paivakoteja',
        'sv' => 'https://www.hel.fi/sv/fostran-och-utbildning/smabarnspedagogik/smabarnspedagogik-pa-daghem/sok-kommunala-daghem',
        'en' => 'https://www.hel.fi/en/childhood-and-education/early-childhood-education/early-childhood-education-in-daycare-centres/search-municipal-daycare-centres',
      ],
      InternalSearchLink::PlowingSchedules => [
        'fi' => 'https://www.hel.fi/fi/kaupunkiymparisto-ja-liikenne/kunnossapito/katujen-kunnossapito/katujen-talvikunnossapito',
        'sv' => 'https://www.hel.fi/sv/stadsmiljo-och-trafik/underhall/gatuunderhall/vinterunderhall-av-gator',
        'en' => 'https://www.hel.fi/en/urban-environment-and-traffic/general-maintenance/street-maintenance/winter-street-maintenance',
      ],
      InternalSearchLink::NewsArchive => [
        'fi' => 'https://www.hel.fi/fi/uutiset/etsi-uutisia',
        'sv' => 'https://www.hel.fi/sv/nyheter/sok-efter-nyheter',
        'en' => 'https://www.hel.fi/en/news/search-for-news',
      ]
    };
  }

}
