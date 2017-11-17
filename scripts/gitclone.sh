#!/usr/bin/env bash

cd /var/www/
git clone --depth=1 --no-single-branch https://$GITHUB_TOKEN:x-oauth-basic@github.com/magento/magento2ce.git
cd magento2ce
git checkout $1

cd /var/www/
git clone --depth=1 --no-single-branch https://$GITHUB_TOKEN:x-oauth-basic@github.com/magento/magento2ee.git
cd magento2ee
git checkout $2