#!/usr/bin/env bash

magento setup:install --backend-frontname="admin" \
--db-host="db" \
--db-name="magento2" \
--db-user="root" \
--db-password="root" \
--admin-user="admin" \
--admin-password="admin123" \
--admin-email="test@user.com" \
--admin-use-security-key='0' \
--admin-firstname="John" \
--admin-lastname="Doe" \
--base-url="http://localhost" \
--cleanup-database;

magento cache:flush;
