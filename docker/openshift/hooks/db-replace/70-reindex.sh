#!/bin/sh

# Reindex news items.
drush sapi-c news
drush sapi-rt news
drush sapi-i news
drush cr
