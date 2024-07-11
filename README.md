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

News items are almost entirely specific to the Etusivu instance, with the exception of high school news on the KASKO
instance. All other news items and articles visible on other core instances are originally written in this instance and
displayed on other instances as external entities. These external items are displayed on the `news_list` paragraph that
is not available on this instance since the news items and articles are local entities here.
News items are provided by the `helfi_platform_config` modules `helfi_node_news_item` module.

#### News article (news_article)

News articles are listed among news items and they behave in similar way. News article is based on the news item
structure‚ but has few distinct differences:
- Main image is required. Hero block is composed of this main image, title and the lead paragraph.
- You can't change the article to a updating news.
- News articles have their own top news article flow where news articles can be added.
News articles are provided by the `helfi_node_news_article` module.

### Global navigation

Global navigation refers to the common navigation elements that can be found on all the core instances. This instance
collects main navigation items from all core instances and serves them through an API as a collection. Additionally, the
common footer, header and language navigations are provided by this instance. See the `helfi_global_navigation` module
and [documentation/menu.md](/documentation/menu.md) for more information.

### Global announcements

From this instance, you are able to create announcements that are displayed on all other hel.fi core instances. Unlike
other instances, the announcement node form includes a `Publish on external site` checkbox that is used to create these
global announcements. Code related to the global announcements can be found in `helfi_global_announcement` under
`helfi_platform_config`.

### Enabled languages

This instance, unlike other core instances, has more than the three main languages enabled. These additional languages
are referred to as alternative languages.

### Custom paragraphs

#### Current (current)

TBD

#### Latest news (front_page_latest_news)

TBD

#### News update (news_update)

News update is used on updating news items, and it can be referred to the `field_news_item_updating_news` field. This is
the only place it can be used and it basically contains the information of one update on a updating news story. For
example if there is a updating news story about a soccer match and there is a new goal that could be one news update.

#### Top news (front_page_top_news)

TBD
