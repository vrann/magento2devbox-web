<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Abstract class for option containers
 */
abstract class AbstractOptions
{
    /**
     * Get option config
     *
     * @param string $name
     * @param array $options
     * @return mixed
     * @throws \Exception
     */
    public static function get($name, $options = [])
    {
        if (!array_key_exists($name, static::getOptions())) {
            throw new \Exception(sprintf('Option "%s" does not exist!', $name));
        }

        return array_replace_recursive(static::getOptions()[$name], $options);
    }

    /**
     * Get all options of one type
     *
     * @return array
     */
    protected static abstract function getOptions();
}
