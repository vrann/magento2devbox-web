<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Container for Magento Cloud options
 */
class MagentoCloud extends AbstractOptions
{
    const INSTALL = 'magento-cloud-install';
    const KEY_REUSE = 'magento-cloud-key-reuse';
    const KEY_CREATE = 'magento-cloud-key-create';
    const KEY_NAME = 'magento-cloud-key-name';
    const KEY_SWITCH = 'magento-cloud-key-switch';
    const KEY_ADD = 'magento-cloud-key-add';
    const PROJECT = 'magento-cloud-project';
    const PROJECT_SKIP = 'magento-cloud-project-skip';
    const BRANCH = 'magento-cloud-branch';
    const BRANCH_SKIP = 'magento-cloud-branch-skip';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::INSTALL => [
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to get sources from Magento Cloud.',
                'question' => 'Do you want to initialize from Magento Cloud? %default%'
            ],
            static::KEY_REUSE => [
                'boolean' => true,
                'default' => true,
                'description' => 'Whether to use existing SSH key for Magento Cloud.',
                'question' => 'Do you want to use existing SSH key? %default%'
            ],
            static::KEY_CREATE => [
                'boolean' => true,
                'default' => true,
                'description' => 'Do you want to create new SSH key?',
                'question' => 'Do you want to create new SSH key? %default%'
            ],
            static::KEY_NAME => [
                'default' => 'id_rsa',
                'description' => 'Name of the SSH key to use with Magento Cloud.',
                'question' => 'What is the name of the SSH key to use with the Magento Cloud? %default%'
            ],
            static::KEY_SWITCH => [
                'virtual' => true,
                'boolean' => true,
                'default' => true,
                'question' => 'File with the key does not exists, do you want to enter different name? %default%'
            ],
            static::KEY_ADD => [
                'boolean' => true,
                'default' => true,
                'description' => 'Whether to add SSH key to Magento Cloud.',
                'question' => 'Do you want to add key to the Magento Cloud? %default%'
            ],
            static::PROJECT => [
                'description' => 'Magento Cloud project to clone.',
                'question' => 'Please select project to clone'
            ],
            static::PROJECT_SKIP => [
                'virtual' => true,
                'boolean' => true,
                'default' => true,
                'question' => 'You haven\'t entered project name. Do you want to continue? %default%'
            ],
            static::BRANCH => [
                'default' => 'master',
                'description' => 'Magento Cloud branch to clone from.',
                'question' => 'What branch do you want to clone from? %default%'
            ],
            static::BRANCH_SKIP => [
                'virtual' => true,
                'boolean' => true,
                'default' => true,
                'question' => 'You haven\'t entered branch name. Do you want to continue? %default%'
            ]
        ];
    }
}
