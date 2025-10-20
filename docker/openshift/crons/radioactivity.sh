#!/bin/sh

while true
do
  drush helfi:radioactivity
  # Run every 3 hours
  sleep 10200
done
