#!/usr/bin/env bash

echo "[IN PROGRESS] Sync Started." > /var/www/magento2/status.html
sed -i 's/^\(\s*DirectoryIndex\s*\).*$/\1status.html/' /etc/apache2/sites-enabled/apache-default.conf
service apache2 restart

echo "[IN PROGRESS] Sync Started. Copying started" > /var/www/magento2/status.html
cp -rf /home/magento2/magento2 /var/www
chown -R magento2:magento2 /var/www/magento2
chown -R magento2:magento2 /home/magento2/magento2
echo "[IN PROGRESS] Copying Finished" > /var/www/magento2/status.html

echo "[IN PROGRESS] Unison sync started" > /var/www/magento2/status.html
unison magento2

echo "[DONE] Sync Finished" > /var/www/magento2/status.html
sed -i 's/^\(\s*DirectoryIndex\s*\).*$/\1index.html/' /etc/apache2/sites-enabled/apache-default.conf
service apache2 restart
rm -rf /var/www/magento2/status.html

supervisord -n -c /etc/supervisord.conf