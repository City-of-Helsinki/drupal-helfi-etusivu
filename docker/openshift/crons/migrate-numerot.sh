#!/bin/bash

source /init.sh

while true
do
  # Allow migrations to be run every 6 hours and reset stuck migrations every 6 hours.
  drush migrate:import helfi_search_organization --no-progress --reset-threshold 21600 --interval 21600
  drush migrate:import helfi_search_contact --no-progress --reset-threshold 21600 --interval 21600
  # Sleep for 12 hours.
  sleep 43200
done
