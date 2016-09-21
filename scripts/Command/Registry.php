<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command;

/**
 * Class for communication between commands
 */
class Registry
{
    /**
     * @var array
     */
    private static $data = [];

    /**
     * Get value
     *
     * @param string $key
     * @return mixed|null
     */
    public static function get($key)
    {
        return static::has($key) ? static::$data[$key] : null;
    }

    /**
     * Set value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set($key, $value)
    {
        static::$data[$key] = $value;
    }

    /**
     * Check if value exists
     *
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return array_key_exists($key, static::$data);
    }
}
