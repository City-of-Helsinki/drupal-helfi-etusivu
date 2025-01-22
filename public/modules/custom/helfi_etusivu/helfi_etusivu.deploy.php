<?php

/**
 * @file
 * Contains etusivu deploy hooks.
 */

declare(strict_types=1);

/**
 * UHF-9741: Set location for neighbourhoods.
 */
function helfi_etusivu_deploy_0001_news_neighbourhoods() : void {
  $neighbourhoods = [
    'Alppiharju' => [60.187933, 24.944132],
    'Eira' => [60.155249, 24.938121],
    'Etu-Töölö' => [60.173279, 24.923774],
    'Haaga' => [60.221837, 24.896392],
    'Hakaniemi' => [60.180465, 24.951613],
    'Hermanni' => [60.195101, 24.966697],
    'Hernesaari' => [60.149268, 24.924342],
    'Herttoniemi' => [60.193202, 25.036063],
    'Honkasuo' => [60.256156, 24.845279],
    'Itäkeskus ja Vartiokylä' => [60.214989, 25.089473],
    'Jätkäsaari' => [60.156744, 24.913439],
    'Kaarela' => [60.251193, 24.881449],
    'Kaartinkaupunki' => [60.165227, 24.948996],
    'Kaivopuisto' => [60.155933, 24.955835],
    'Kalasatama' => [60.185294, 24.980741],
    'Kallio' => [60.184327, 24.949712],
    'Kamppi' => [60.167335, 24.931190],
    'Kannelmäki' => [60.241693, 24.885368],
    'Karhusaari' => [60.250323, 25.220108],
    'Katajanokka' => [60.166662, 24.969216],
    'Keskusta' => [60.169519, 24.952272],
    'Kluuvi' => [60.172580, 24.941144],
    'Koivusaari' => [60.163196, 24.856204],
    'Konala' => [60.236852, 24.845177],
    'Koskela' => [60.218253, 24.966418],
    'Kruununhaka' => [60.172352, 24.956470],
    'Kruunuvuorenranta' => [60.166554, 25.022126],
    'Kulosaari' => [60.185983, 25.008457],
    'Kumpula' => [60.209094, 24.964821],
    'Kuninkaantammi' => [60.261009, 24.890437],
    'Käpylä' => [60.214154, 24.950797],
    'Laajasalo' => [60.171870, 25.043385],
    'Laakso' => [60.192795, 24.916465],
    'Lauttasaari' => [60.158291, 24.874188],
    'Länsisatama' => [60.159699, 24.924461],
    'Malmi' => [60.250980, 25.010506],
    'Malminkartano' => [60.247517, 24.862129],
    'Meilahti' => [60.191904, 24.898236],
    'Mellunkylä' => [60.233563, 25.102157],
    'Munkkiniemi' => [60.198247, 24.875977],
    'Mustikkamaa-Korkeasaari' => [60.180563, 24.990058],
    'Myllypuro' => [60.223721, 25.067943],
    'Oulunkylä' => [60.229094, 24.963609],
    'Pakila' => [60.244177, 24.948121],
    'Pasila' => [60.203116, 24.926870],
    'Pitäjänmäki' => [60.222972, 24.862067],
    'Pukinmäki' => [60.245141, 24.988965],
    'Punavuori' => [60.161450, 24.937520],
    'Ruoholahti' => [60.163818, 24.908564],
    'Ruskeasuo' => [60.202629, 24.905525],
    'Salmenkallio' => [60.263065, 25.192160],
    'Santahamina' => [60.147226, 25.051203],
    'Suomenlinna' => [60.145609, 24.986295],
    'Suurmetsä' => [60.265570, 25.079241],
    'Suutarila' => [60.280899, 25.010958],
    'Sörnäinen' => [60.186503, 24.968059],
    'Taka-Töölö' => [60.184170, 24.923262],
    'Talosaari' => [60.242051, 25.197464],
    'Tammisalo' => [60.191309, 25.063848],
    'Tapaninkylä' => [60.262263, 25.011138],
    'Toukola' => [60.206072, 24.972955],
    'Tuomarinkylä' => [60.256383, 24.966787],
    'Ulkosaaret' => [60.089292, 24.926002],
    'Ullanlinna' => [60.158457, 24.948937],
    'Ultuna' => [60.278678, 25.195271],
    'Vallila' => [60.194432, 24.956953],
    'Vanhakaupunki' => [60.216221, 24.981143],
    'Vartiokylä' => [60.217183, 25.095837],
    'Vartiosaari' => [60.184930, 25.078028],
    'Viikki' => [60.224815, 25.020019],
    'Villinki' => [60.158338, 25.114887],
    'Vuosaari' => [60.209362, 25.147469],
    'Östersundom' => [60.251257, 25.182209],
  ];

  $storage = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term');

  foreach ($neighbourhoods as $name => $location) {
    $terms = $storage->loadByProperties(['name' => $name, 'vid' => 'news_neighbourhoods']);
    if (!$terms) {
      continue;
    }

    [$latitude, $longitude] = $location;
    $term = reset($terms);
    $term->set('field_location', [
      'latitude' => $latitude,
      'longitude' => $longitude,
    ]);
    $term->save();
  }

}
