# Menu API

@todo Improve this

## Available languages

- fi
- en
- sv

## Global menus

Allows you to fetch all or individual "main" menus. This can be used to build main menu from each

### Endpoints

Fetch main menu for individual instance:

- `GET /{langcode}/api/v1/global-menu/{id}`

Update or create new main menu:
- `POST /{langcode}/api/v1/global-menu/{id}`

Get all available main menus:
- `GET /{langcode}/api/v1/global-menu`

### Authentication

- Basic auth

## Shared menus

### Endpoints

- `GET /{langcode}/jsonapi/menu_items/{id}`.

### Authentication

- Basic auth
