<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Enum;

/**
 * Enum class for internal search links.
 *
 * For now this class is just a "stupid" enum class with a list of links.
 * This should probably be reworked into a more robust solution in the future.
 */
enum InternalSearchLink {
  case HEALTH_STATIONS;
  case CHILD_HEALTH_STATIONS;
  case SCHOOLS;
  case PLAYGROUNDS_FAMILY_HOUSES;
  case DAYCARES;
  case PLOWING_SCHEDULES;

  /**
   * Return array of link translations.
   *
   * @return array
   *   Array of link translations.
   */
  public function getLinkTranslations() : array {
    return match ($this) {
      InternalSearchLink::HEALTH_STATIONS => [
        'fi' => 'https://www.hel.fi/fi/sosiaali-ja-terveyspalvelut/terveydenhoito/terveysasemat',
        'sv' => 'https://www.hel.fi/sv/social-och-halsovardstjanster/halsovard/halsostationer',
        'en' => 'https://www.hel.fi/en/health-and-social-services/health-care/health-stations',
      ],
      InternalSearchLink::CHILD_HEALTH_STATIONS => [
        'fi' => 'https://www.hel.fi/fi/sosiaali-ja-terveyspalvelut/lasten-ja-perheiden-palvelut/aitiys-ja-lastenneuvolat',
        'sv' => 'https://www.hel.fi/sv/social-och-halsovardstjanster/tjanster-for-barn-och-familjer/modra-och-barnradgivningarna',
        'en' => 'https://www.hel.fi/en/health-and-social-services/child-and-family-services/maternity-and-child-health-clinics',
      ],
      InternalSearchLink::SCHOOLS => [
        'fi' => 'https://www.hel.fi/fi/kasvatus-ja-koulutus/perusopetus/peruskoulut',
        'sv' => 'https://www.hel.fi/sv/fostran-och-utbildning/grundlaggande-utbildning/grundskolor',
        'en' => 'https://www.hel.fi/en/childhood-and-education/basic-education/comprehensive-schools',
      ],
      InternalSearchLink::PLAYGROUNDS_FAMILY_HOUSES => [
        'fi' => 'https://www.hel.fi/fi/kasvatus-ja-koulutus/leikkipuistot/leikkipuistot-ja-perhetalot',
        'sv' => 'https://www.hel.fi/sv/fostran-och-utbildning/lekparker/sok-lekparker-och-familjehus',
        'en' => 'https://www.hel.fi/en/childhood-and-education/playgrounds/find-playgrounds-and-family-houses',
      ],
      InternalSearchLink::DAYCARES => [
        'fi' => 'https://www.hel.fi/fi/kasvatus-ja-koulutus/varhaiskasvatus/varhaiskasvatus-paivakodissa/etsi-kunnallisia-paivakoteja',
        'sv' => 'https://www.hel.fi/sv/fostran-och-utbildning/smabarnspedagogik/smabarnspedagogik-pa-daghem/sok-kommunala-daghem',
        'en' => 'https://www.hel.fi/en/childhood-and-education/early-childhood-education/early-childhood-education-in-daycare-centres/search-municipal-daycare-centres',
      ],
      InternalSearchLink::PLOWING_SCHEDULES => [
        'fi' => 'https://www.hel.fi/fi/kaupunkiymparisto-ja-liikenne/kunnossapito/katujen-kunnossapito/katujen-talvikunnossapito',
        'sv' => 'https://www.hel.fi/sv/stadsmiljo-och-trafik/underhall/gatuunderhall/vinterunderhall-av-gator',
        'en' => 'https://www.hel.fi/en/urban-environment-and-traffic/general-maintenance/street-maintenance/winter-street-maintenance',
      ],
    };
  }

}
