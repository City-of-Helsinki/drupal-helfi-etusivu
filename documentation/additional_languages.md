# Additional languages

## Description

This instance has limited support for additional languages (including RTL languages).
Adding new languages differs from the normal workflow and takes a few extra steps (requirements of the infrastructure)
More information can be found from confluence "Muut kielet -dokumentti".

### Initially added languages

- ar - arabic,
- de - german,
- es - spanish,
- et - estonian,
- fa - farsi,
- fr - french,
- ru - russian,
- uk - ukrainian,
- se - northern sami,
- so - somali,
- zh-hans - simplified chinese

## How to add new languages

### Drupal configuration

- Add new language via UI and export configurations
- [Example implementation based on task UHF-8141](https://github.com/City-of-Helsinki/drupal-helfi-etusivu/pull/294/files)

### Adding new languages for proxy settings

Some configurations are required for proxy module.

- New Proxy prefixes must be added for environments' `.settings.php` -file
  - Unlike other sites, Etusivu does not need production configuration added to conf/cmi/helfi_proxy.settings.yml
- [Proxy module documentation](https://github.com/City-of-Helsinki/drupal-module-helfi-proxy/blob/main/README.md#site-prefix)
- [Example implementation based on task](https://github.com/City-of-Helsinki/drupal-helfi-etusivu/commit/115e345ccf9001117bc632ab3dc1b2b1a5ec7eef)

### Platform redirects

Getting translations to work on live environments, redirects must be requested from PLATTA team.
Request proxy redirects by creating ticket to platta-jira or from Slack channel: "platta"

The request itself should contain the site name and the prefixes that requires redirects. The prefixes are the same you added to the settings.php:

`{helfi-sitename}:`

`{Environment name}:`
- `/{langcode}/{site-prefix}`

`...`

For example in case of AR and DE languages:

`Test-environment:`
- `/ar/test-etusivu`
- `/de/test-etusivu`

`Staging-environment:`
- `/ar/staging-etusivu`
- `/de/staging-etusivu`

`Production`
- `/ar`
- `/de`


### Adding languages to menus

- After everything else has been done and released, the added languages can be set to correct menu in every environment.
