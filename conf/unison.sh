#!/usr/bin/env bash

# Run unison server
su - magento2 -c "unison -repeat=watch magento2 > /home/magento2/custom_unison.log 2>&1 &"
