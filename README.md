# City of Helsinki - Etusivu Drupal project

Etusivu is the front page of the hel.fi project, featuring elements like the global menu, news, and articles, which are
also used on other pages. Additionally, Etusivu offers alternative language options not available on other instances.

## Environments

Env | Branch | Drush alias | URL
--- | ------ | ----------- | ---
development | * | - | http://helfi-etusivu.docker.so/
production | main | @main | TBD

## Requirements

You need to have these applications installed to operate on all environments:

- [Docker](https://github.com/druidfi/guidelines/blob/master/docs/docker.md)
- [Stonehenge](https://github.com/druidfi/stonehenge)
- For the new person: Your SSH public key needs to be added to servers

## Create and start the environment

For the first time (new project):

``
$ make new
``

And following times to start the environment:

``
$ make up
``

NOTE: Change these according of the state of your project.

## Login to Drupal container

This will log you inside the app container:

```
$ make shell
```

## Instance specific customizations

Etusivu instance has quite a lot of unique features because it is used as a master data source in many features such as
global navigation and news.

### Custom node types

#### News item (news_item)

News items are mostly specific to the Etusivu instance, except for high school news on the KASKO instance. All other
news items and articles visible on other core instances are originally written in this instance and displayed on other
instances as external entities. These external items are displayed on the `news_list` paragraph that is not available
on this instance since the news items and articles are local entities here. News items are provided by the
`helfi_platform_config` modules `helfi_node_news_item` module.

#### News article (news_article)

News articles are listed among news items and they behave in similar way. News article is based on the news item
structure‚ but has few distinct differences:
- Main image is required. Hero block is composed of this main image, title and the lead paragraph.
- You can't change the article to a updating news.
- News articles have their own top news article flow where news articles can be added.
News articles are provided by the `helfi_node_news_article` module.

### Custom paragraphs

#### Current (current)

The _current_ paragraph is a curated list of links with seasonal illustration that you can add to landing pages. You
can select the season from the `field_seasons` and it will change the illustration. The logic can be found in the
instances `hdbt_subtheme` preprocess [here](https://github.com/City-of-Helsinki/drupal-helfi-etusivu/blob/e2643195b8fc2989da835313c052ae533b8e0143/public/themes/custom/hdbt_subtheme/hdbt_subtheme.theme#L108). The background color of the paragraph is the secondary color of the
selected color palette of the node that the paragraph is added to.

#### Latest news (front_page_latest_news)

_Latest news_ paragraph lists six latest news and links to the news archive page. You can select between two designs
for the listing (minimal and cards). The link for the news archive page is hardcoded, and it is done in the view called
`frontpage_news`. The configuration is found from `views.view.frontpage_news.yml` and the link to news archive page is
defined [here](https://github.com/City-of-Helsinki/drupal-helfi-etusivu/blob/e2643195b8fc2989da835313c052ae533b8e0143/conf/cmi/views.view.frontpage_news.yml#L594).

#### News update (news_update)

_News update_ is used on updating news items, and it can be referred to the `field_news_item_updating_news` field. This
is the only place it can be used, and it essentially contains the information of one update on an ongoing news story.
For example, if there is an updating news story about a soccer match, a new goal would be one news update.

#### Top news (front_page_top_news)

The _top news_ paragraph lists the news items or articles that have been added to the top news/articles flow. The
selection of what to show is determined by the `field_listing_type`, and the paragraph can be added to landing pages.

#### News archive (news_archive)

The _news_archive_ paragraph provides the news archive search that can be added to landing pages. The news archive is
a React search that uses views listing (`news_archive_index`) as a fallback when JavaScript is not enabled. All the
React searches are in the `hdbt` theme and so most of the logic related to the functionality is also found in the
`hdbt` theme. The _news_archive_ paragraph has editable title and description.
- React search code can be found under the `hdbt` theme [here](https://github.com/City-of-Helsinki/drupal-hdbt/tree/main/src/js/react/apps/news-archive).
- Additional configuration for the React app is under the `hdbt_subtheme` theme function
`hdbt_subtheme_preprocess_paragraph` [here](https://github.com/City-of-Helsinki/drupal-helfi-etusivu/blob/dev/public/themes/custom/hdbt_subtheme/hdbt_subtheme.theme)
- Fallback view when JavaScript is not enabled can be found in the `/conf/cim` folder [here](https://github.com/City-of-Helsinki/drupal-helfi-etusivu/blob/dev/conf/cmi/views.view.news_archive_index.yml).

### Global navigation

Global navigation refers to the common navigation elements that can be found on all the core instances. This instance
collects main navigation items from all core instances and serves them through an API as a collection. Additionally, the
common footer, header and language navigations are provided by this instance. See the `helfi_global_navigation` module
and [documentation/menu.md](/documentation/menu.md) for more information.

### Global announcements

From this instance, you are able to create announcements that are displayed on all other hel.fi core instances. Unlike
other instances, the announcement node form includes a `Publish on external site` checkbox that is used to create these
global announcements. Code related to the global announcements can be found in `helfi_global_announcement` under
`helfi_platform_config` and the configuration for the `Publish on external site` is in the conf/cmi folder of this
instance and the configuration rewrite is [here](https://github.com/City-of-Helsinki/drupal-helfi-etusivu/blob/e2643195b8fc2989da835313c052ae533b8e0143/public/modules/custom/helfi_etusivu_config/config/rewrite/core.entity_form_display.node.announcement.default.yml).

### Enabled languages

This instance, unlike other core instances, has more than the three main languages enabled. These additional languages
are referred to as alternative languages.
