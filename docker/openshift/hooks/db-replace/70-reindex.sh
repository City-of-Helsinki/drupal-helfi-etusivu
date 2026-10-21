#!/bin/sh

# Reindex news items.
drush sapi-c news
drush sapi-rt news

# The parallel tracker lets each worker claim its own items.
i=0
while [ $i -lt 8 ]; do
  drush sapi-i news &
  i=$((i+1))
done
wait

drush cr
