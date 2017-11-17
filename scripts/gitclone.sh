#!/usr/bin/env bash

cd /var/www/magento2
git clone --depth=1 --no-single-branch https://$GITHUB_TOKEN:x-oauth-basic@github.com/magento/magento2ce.git
cd magento2ce
git checkout $1

cd /var/www/magento2
git clone --depth=1 --no-single-branch https://$GITHUB_TOKEN:x-oauth-basic@github.com/magento/magento2ee.git
cd magento2ee
git checkout $2

cp -R /var/www/magento2/magento2ce/* /var/www/magento2
cp -R /var/www/magento2/magento2ee/* /var/www/magento2

rm -rf /var/www/magento2/app/code/Magento/TestModule*
rm -rf /var/www/magento2/var/*cache
rm -rf /var/www/magento2/generated/code/
rm -rf /var/www/magento2/var/di
rm -rf /var/www/magento2/var/generation
rm -rf /var/www/magento2/dev/tests/integration/tmp/sandbox*

cd /var/www/magento2;
composer install;