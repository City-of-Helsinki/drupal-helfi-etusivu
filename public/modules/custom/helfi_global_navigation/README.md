# HELfi global navigation

## Note: This module is not completed and is not ready for production (14.7.2022)

## TODO:
- Proper authentication for POST endpoint, for example:
  - whitelist check for allowed referrers (request coming from allowed site)
  - Auth token
- response caching

## Description

Etusivu-instance is the main repository for global menus. It serves global menus to other helfi-instances.


## Features

- Create/update single aggregated main-navigation using main-navigations sent from other instances
- Create other global navigations from Etusivu-instance menus
- Respond to the requests sent by other instances
  - Update/create main-navigation
  - Fetch any global navigation


### Create / update aggregated main navigation

- Menu creation is handled by MenuController's post method.
  - `Drupal\helfi_global_navigation\Controller\MenuController::post POST`
  - Post-method takes project name as a parameter
- When post request is received, GlobalMenu-entity & entity translations are created.
  - Updater expects all language versions to be sent and creates entity translations always.
- After entities are updated, return json is cached and returned as response.


### Fetch main navigation and other global navigations

- Menu fetching is handled by MenuController's list method.
  - `Drupal\helfi_global_navigation\Controller\MenuController::list GET`
  - List-method takes the name of the navigation as a parameter
  - List-method can return any global menu (main, footer, header-top...)
- List method checks cache for proper menu-json to return
  - If nothing is cached, it recreates the menu-json from GlobalMenu-entities
- Menu-json is returned.


