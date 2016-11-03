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
    const HOST = 'elastic-host';
    const PORT = 'elastic-port';
    const ELASTIC_MODULE_NAME = 'Magento_Elasticsearch';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
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
