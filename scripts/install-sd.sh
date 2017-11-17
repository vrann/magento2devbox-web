#!/usr/bin/env bash

/Users/okorshenko/www/libs/bash/ee/relink.sh

/Users/okorshenko/www/libs/bash/ee/composer.sh

/Users/okorshenko/www/libs/bash/ee/install.sh

cp /Users/okorshenko/www/magento2/magento2ce/magento2ee/composer.json /Users/okorshenko/www/magento2/magento2ce/composer.json
cp /Users/okorshenko/www/magento2/magento2ce/magento2ee/composer.lock /Users/okorshenko/www/magento2/magento2ce/composer.lock

rm -rf /Users/okorshenko/www/magento2/magento2ce/vendor/*;
cd /Users/okorshenko/www/magento2/magento2ce;
composer install;

/Users/okorshenko/www/libs/bash/install-sd.sh

git checkout composer.json;
git checkout composer.lock;
