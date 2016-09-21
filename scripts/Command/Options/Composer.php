<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

require_once __DIR__ . '/AbstractOptions.php';

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
                'description' => 'Composer public key for Magento.',
                'question' => 'Enter your Magento public key'
            ],
            static::PRIVATE_KEY => [
                'description' => 'Composer private key for Magento.',
                'question' => 'Enter your Magento private key'
            ]
        ];
    }
}
