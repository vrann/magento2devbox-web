<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Container for Composer options
 */
class Composer extends AbstractOptions
{
    const PUBLIC_KEY = 'composer-public-key';
    const PRIVATE_KEY = 'composer-private-key';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::PUBLIC_KEY => [
                'default' => strlen(getenv('MAGENTO_PUBLIC_KEY')) > 0 ? getenv('MAGENTO_PUBLIC_KEY') : null,
                'description' => 'Composer public key for Magento.',
                'question' => 'Enter your Magento public key',
                'validationPattern' => '/^[\w\d]+$/',
                'validationAttempts' => 3
            ],
            static::PRIVATE_KEY => [
                'default' => strlen(getenv('MAGENTO_PRIVATE_KEY')) > 0 ? getenv('MAGENTO_PRIVATE_KEY') : null,
                'description' => 'Composer private key for Magento.',
                'question' => 'Enter your Magento private key',
                'validationPattern' => '/^[\w\d]+$/',
                'validationAttempts' => 3
            ]
        ];
    }
}
