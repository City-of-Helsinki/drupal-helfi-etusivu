#!/bin/sh

(while true
do
 drush stomp:worker helfi_api_base_revision --items-limit 100
done) &
