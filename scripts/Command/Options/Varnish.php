<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Container for Varnish options
 */
class Varnish extends AbstractOptions
{
    const FPC_INSTALLED = 'fpc-installed';
    const FPC_SETUP = 'varnish-fpc-setup';
    const CONFIG_PATH = 'varnish-config-path';
    const HOME_PORT = 'varnish-home-port';
    const HOST = 'varnish-host';

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
                'default' => '/home/magento2/configs/varnish/default.vcl',
                'description' => 'Configuration file path for Varnish.',
                'question' => 'Please enter configuration file path for Varnish %default%'
            ],
            static::HOME_PORT => [
                'description' => 'Varnish port on home machine.',
                'question' => 'Please enter Varnish port on home machine %default%'
            ],
            static::HOST => [
                'default' => 'varnish',
                'description' => 'Varnish host',
                'question' => 'Please enter Varnish host %default%'
            ]
        ];
    }
}
