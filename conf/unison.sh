#!/usr/bin/env bash

while [ 1 == 1 ]; do
#    ps aux | grep "[u]nison -repeat=watch magento2-var"
#    if [ $? != 0 ]
#    then
#        (su - magento2 -c 'unison -repeat=watch magento2-var') &
#    fi
    ps aux | grep "[u]nison -debug all -repeat=watch magento2-code"
    if [ $? != 0 ]
    then
        (su - magento2 -c 'unison -debug all -repeat=watch magento2-code') &
    fi
    sleep 10
done
