#!/usr/bin/env bash

magento setup:install --backend-frontname=$MAGENTO_BACKEND_PATH \
--db-host="db" \
--db-name="magento2" \
--db-user="root" \
--db-password="root" \
--admin-user=$MAGENTO_ADMIN_USER \
--admin-password=$MAGENTO_ADMIN_PASSWORD \
--admin-email="test@user.com" \
--admin-use-security-key='0' \
--admin-firstname="John" \
--admin-lastname="Doe" \
--base-url="http://localhost:"$1"/" \
--cleanup-database;

magento cache:flush;
