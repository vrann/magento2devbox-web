<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Container for ElasticSearch options
 */
class ElasticSearch extends AbstractOptions
{
    const ES_SETUP = 'elastic-setup';
    const HOST = 'elastic-host';
    const PORT = 'elastic-port';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::ES_SETUP => [
                'initial' => true,
                'boolean' => true,
                'default' => (boolean)getenv('USE_ELASTICSEARCH'),
                'description' => 'Whether to use ElasticSearch as the search engine.',
                'question' => 'Do you want to use ElasticSearch as the Magento search engine? %default%'
            ],
            static::HOST => [
                'initial' => true,
                'default' => 'elasticsearch',
                'description' => 'Magento ElasticSearch host.',
                'question' => 'Please enter magento ElasticSearch host %default%'
            ],
            static::PORT => [
                'initial' => true,
                'default' => '9200',
                'description' => 'Magento ElasticSearch port.',
                'question' => 'Please enter magento ElasticSearch port %default%'
            ]
        ];
    }
}
