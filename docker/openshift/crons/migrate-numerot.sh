#!/bin/bash

source /init.sh

while true
do
  # Allow migrations to be run every hour and reset stuck migrations every hour.
  drush migrate:import helfi_search_organization --no-progress --reset-threshold 3600 --interval 3600
  drush migrate:import helfi_search_contact --no-progress --reset-threshold 3600 --interval 3600
  # Sleep for 12 hours.
  sleep 43200
done
