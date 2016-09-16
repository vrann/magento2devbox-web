#!/usr/bin/env bash

#cp -rf /home/magento2/magento2 /var/www
#rsync -avhW --no-compress /home/magento2/magento2 /var/www/magento2
#chown magento2:magento2 /var/www/magento2

supervisord -n -c /etc/supervisord.conf