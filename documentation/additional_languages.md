# Additional languages

## Description

This instance has limited support for additional languages (including RTL languages).
Adding new languages takes few extra steps required by the environment.

### Initially added languages

- ar, de, es, et, fa, fr, ru, uk, se, so, zh-hans

## How to add new languages

### Drupal configuration

- Add new language via UI and export configurations
- [Example implementation based on task UHF-8141](https://github.com/City-of-Helsinki/drupal-helfi-etusivu/pull/294/files)

### Adding new languages to proxy

- [Proxy module documentation](https://github.com/City-of-Helsinki/drupal-module-helfi-proxy/blob/main/README.md)
- [Example implementation based on task](https://github.com/City-of-Helsinki/drupal-helfi-etusivu/commit/115e345ccf9001117bc632ab3dc1b2b1a5ec7eef)

### Platform redirects

Getting translations to work on live environments, redirects must be requested from PLATTA team.

- Request proxy redirects
- [Example request in PLATTA jira](https://helsinkisolutionoffice.atlassian.net/browse/PLATTA-4660)

