# Menu API

Available languages (`{langcode}`):

- fi
- en
- sv

## Global navigation

Allows you to update/retrieve all or instance specific main-navigations.

### Fetch

Get main menu links for individual instance:

- `GET /{langcode}/api/v1/global-menu/{id}`

Get all available main menus:
- `GET /{langcode}/api/v1/global-menu`

### Update/create

Update or create a main menu:
- `POST /{langcode}/api/v1/global-menu/{id}`

#### Fields

- `site_name` (required)
- `menu_tree`: (required)
  - `url`: The menu link URL (required)
  - `id`: The menu link ID (required)
  - `name`: The menu link name (required)
  - `external`: Whether the link is external or internal. `false` = internal, `true` = external  (optional)
  - `attributes`: HTML attributes (optional)
    - `lang`: The lang attribute (optional)
    - `data-protocol`: The protocol (`tel`, `mailto` for example) (optional)
    - `data-external`: Same as `external` field above (optional)
  - `weight`: The weight. Sorted from low to high (optional)
  - `sub_tree`: A recursive menu tree. Contains same elements as above and these additional fields:
    - `parentId`: The immediate parent ID (required)
    - `parents`: A list of "parent" IDs (optional)

The `menu_tree` field will be validated against [public/modules/custom/helfi_global_navigation/assets/schema.json](/public/modules/custom/helfi_global_navigation/assets/schema.json) JSON-schema.

See [City-of-Helsinki/drupal-module-helfi-navigation](https://github.com/City-of-Helsinki/drupal-module-helfi-navigation) for an example implementation using this API.

Example payload:

```json
{
  "site_name": "Kaupunkiympäristö ja liikenne",
  "menu_tree": {
    "url": "https://helfi-kymp.docker.so/fi/kaupunkiymparisto-ja-liikenne/kaupunkiymparisto-ja-liikenne",
    "id": "base:kaupunkiymp_rist_ja_liikenne",
    "name": "Kaupunkiympäristö ja liikenne",
    "external": false,
    "attributes": {},
    "weight": 0,
    "sub_tree": [
      {
        "url": "https://helfi-kymp.docker.so/fi/kaupunkiymparisto-ja-liikenne/pysakointi",
        "id": "menu_link_content:7c9ddcc2-4d07-4785-8940-046b4cb85fb4",
        "name": "Pysäköinti",
        "parentId": "base:kaupunkiymp_rist_ja_liikenne",
        "attributes": {
          "lang": "fi-FI"
        },
        "external": false,
        "hasItems": true,
        "expanded": false,
        "parents": [
          "menu_link_content:7c9ddcc2-4d07-4785-8940-046b4cb85fb4",
          "base:kaupunkiymp_rist_ja_liikenne"
        ],
        "weight": -50
      }
    ]
  }
}
```

### Authentication

- Basic auth

## Shared menus

Fetch all menu-links for given menu:

- `GET /{langcode}/api/v1/menu/{menu_name}`.

Available menus (`{menu_name}`):
- `footer-bottom-navigation`
- `footer-top-navigation`
- `footer-top-navigation-2`
- `header-top-navigation`
- `header-language-links`

### Authentication

- Basic auth
