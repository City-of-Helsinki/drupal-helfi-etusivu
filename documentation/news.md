# News APIs

## JSON:API

Uses Drupal's JSON:API module. See https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module

### Base URL

Base URL for News API endpoints is `https://www.hel.fi/{langcode}/jsonapi/node/news`. See the [Available languages](#available-languages) section for `{langcode}` substitutions.

### Available languages

- fi
- en
- sv

## Elastic search

todo

## RSS feed

Base URL: https://www.hel.fi

### Available languages

- https://www.hel.fi/fi/uutiset/rss
- https://www.hel.fi/en/news/rss
- https://www.hel.fi/sv/nyheter/rss

### Filters

You can find the values for the filters by going to `https://www.hel.fi/en/news` and checking the corresponding select list in the `Search for news items` section.

#### Topics

`topic`: An array of topic IDs. For example `topic[0]=460`.

#### Neighbourhoods

`neighbourhoods`: An array of neighbourhood IDs. For example `neighbourhoods[0]=403`.

#### Groups

`groups`: An array of group IDs. For example `?groups[0]=5`.
