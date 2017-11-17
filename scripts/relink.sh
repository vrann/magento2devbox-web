#!/bin/sh

rm -rf /var/www/magento2/*
cp -R /var/www/magento2ce/* /var/www/magento2
cp -R /var/www/magento2ee/* /var/www/magento2

rm -rf /var/www/magento2/app/code/Magento/TestModule*
rm -rf /var/www/magento2/var/*cache
rm -rf /var/www/magento2/generated/code/
rm -rf /var/www/magento2/var/di
rm -rf /var/www/magento2/var/generation
rm -rf /var/www/magento2/dev/tests/integration/tmp/sandbox*

cd /var/www/magento2;
composer install;