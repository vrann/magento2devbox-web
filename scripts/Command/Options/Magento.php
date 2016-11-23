<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Container for Magento options
 */
class Magento extends AbstractOptions
{
    const SOURCES_REUSE = 'magento-sources-reuse';
    const INSTALL_FROM_COMPOSER = 'install-from-composer';
    const HOST = 'magento-host';
    const PORT = 'magento-port';
    const PATH = 'magento-path';
    const EDITION = 'magento-edition';
    const BACKEND_PATH = 'magento-backend-path';
    const ADMIN_USER = 'magento-admin-user';
    const ADMIN_PASSWORD = 'magento-admin-password';
    const SAMPLE_DATA_INSTALL = 'magento-sample-data-install';
    const STATIC_CONTENTS_DEPLOY = 'magento-static-contents-deploy';
    const GRUNT_COMPILE = 'magento-grunt-compile';
    const DI_COMPILE = 'magento-di-compile';
    const CRON_RUN = 'magento-cron-run';
    const VERSION = 'magento-version';
    const WARM_UP_STOREFRONT = 'magento-warm-up-storefront';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::SOURCES_REUSE => [
                'initial' => true,
                'boolean' => true,
                'default' => strlen(getenv('MAGENTO_USE_SOURCES_IN_HOST')) > 0 ?
                    (boolean)getenv('MAGENTO_USE_SOURCES_IN_HOST') : false,
                'description' => 'Whether to use existing sources.',
                'question' => 'Do you want to use existing sources? %default%'
            ],
            static::INSTALL_FROM_COMPOSER => [
                'boolean' => true,
                'default' => strlen(getenv('MAGENTO_DOWNLOAD_SOURCES_COMPOSER')) > 0 ?
                    (boolean)getenv('MAGENTO_DOWNLOAD_SOURCES_COMPOSER') : true,
                'description' => 'Whether to use composer create-project.',
                'question' => 'Do you want to use composer installation? %default%'
            ],
            static::HOST => [
                'default' => '127.0.0.1',
                'description' => 'Magento host.',
                'question' => 'Please enter Magento host %default%'
            ],
            static::PATH => [
                'default' => '/var/www/magento2',
                'description' => 'Path to source folder for Magento.',
                'question' => 'Please enter path to source folder for Magento %default%'
            ],
            static::EDITION => [
                'default' => strlen(getenv('MAGENTO_EDITION')) > 0 ? getenv('MAGENTO_EDITION') : 'CE',
                'description' => 'Edition of Magento to install.',
                'question' => 'Which edition of Magento you want to be installed (please, choose CE or EE) %default%'
            ],
            static::VERSION => [
                'default' => strlen(getenv('MAGENTO_VERSION')) > 0 ? getenv('MAGENTO_VERSION') : '',
                'description' => 'Version of Magento to install.',
                'question' => 'Which version of Magento you want to be installed (i.e 2.0.*, 2.1.0 or leave empty for latest) %default%'
            ],
            static::BACKEND_PATH => [
                'initial' => true,
                'default' => strlen(getenv('MAGENTO_BACKEND_PATH')) > 0 ? getenv('MAGENTO_BACKEND_PATH') : 'admin',
                'description' => 'Magento backend path.',
                'question' => 'Please enter backend path %default%'
            ],
            static::ADMIN_USER => [
                'initial' => true,
                'default' => strlen(getenv('MAGENTO_ADMIN_USER')) > 0 ? getenv('MAGENTO_ADMIN_USER') : 'admin',
                'description' => 'Magento admin username.',
                'question' => 'Please enter backend admin username %default%'
            ],
            static::ADMIN_PASSWORD => [
                'initial' => true,
                'default' => strlen(getenv('MAGENTO_ADMIN_PASSWORD')) > 0 ?
                    getenv('MAGENTO_ADMIN_PASSWORD') : 'admin123',
                'description' => 'Magento admin password.',
                'question' => 'Please enter backend admin password %default%'
            ],
            static::SAMPLE_DATA_INSTALL => [
                'boolean' => true,
                'default' => strlen(getenv('MAGENTO_SAMPLE_DATA_INSTALL')) > 0 ?
                    (boolean)getenv('MAGENTO_SAMPLE_DATA_INSTALL') : false,
                'description' => 'Whether to install Sample Data.',
                'question' => 'Do you want to install Sample Data? %default%'
            ],
            static::STATIC_CONTENTS_DEPLOY => [
                'boolean' => true,
                'default' => strlen(getenv('MAGENTO_STATIC_CONTENTS_DEPLOY')) > 0 ?
                    (boolean)getenv('MAGENTO_STATIC_CONTENTS_DEPLOY') : false,
                'description' => 'Whether to pre-deploy all static contents.',
                'question' => 'Do you want to pre-deploy all static assets? %default%'
            ],
            static::GRUNT_COMPILE => [
                'boolean' => true,
                'default' => strlen(getenv('MAGENTO_GRUNT_COMPILE')) > 0 ?
                    (boolean)getenv('MAGENTO_GRUNT_COMPILE') : false,
                'description' => 'Whether to compile CSS out of LESS via Grunt.',
                'question' => 'Do you want to compile CSS out of LESS via Grunt? %default%'
            ],
            static::DI_COMPILE => [
                'boolean' => true,
                'default' => strlen(getenv('MAGENTO_DI_COMPILE')) > 0 ?
                    (boolean)getenv('MAGENTO_DI_COMPILE') : false,
                'description' => 'Whether to create generated files beforehand.',
                'question' => 'Do you want to create generated files beforehand? %default%'
            ],
            static::CRON_RUN => [
                'boolean' => true,
                'default' => strlen(getenv('MAGENTO_CRON_RUN')) > 0 ?
                    (boolean)getenv('MAGENTO_CRON_RUN') : false,
                'description' => 'Whether to generate crontab file for Magento.',
                'question' => 'Do you want to generate crontab file for Magento? %default%'
            ],
            static::WARM_UP_STOREFRONT => [
                'boolean' => true,
                'default' => strlen(getenv('MAGENTO_WARM_UP_STOREFRONT')) > 0 ?
                    (boolean)getenv('MAGENTO_WARM_UP_STOREFRONT') : true,
                'description' => 'Whether to warm up storefront for Magento.',
                'question' => 'Do you want to warm up storefront for Magento to speed up first page load? %default%'
            ]
        ];
    }
}
