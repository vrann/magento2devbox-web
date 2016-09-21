<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

require_once __DIR__ . '/AbstractOptions.php';

/**
 * Container for Varnish options
 */
class Varnish extends AbstractOptions
{
    const FPC_SETUP = 'varnish-fpc-setup';
    const CONFIG_PATH = 'varnish-config-path';
    const HOME_PORT = 'varnish-home-port';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::FPC_SETUP => [
                'initial' => true,
                'boolean' => true,
                'default' => true,
                'description' => 'Whether to use Varnish as Magento full page cache.',
                'question' => 'Do you want to use Varnish as Magento full page cache? %default%'
            ],
            static::CONFIG_PATH => [
                'default' => '/home/magento2/configs/varnish/default.vcl.',
                'description' => 'Magento root directory',
                'question' => 'Please enter output configuration file path %default%'
            ],
            static::HOME_PORT => [
                'default' => 1749,
                'description' => 'Varnish port on home machine.',
                'question' => 'Please enter Varnish port on home machine %default%'
            ]
        ];
    }
}
