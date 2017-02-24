<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Library;

/**
 * Class for module existence check
 */
class ModuleExistence
{
    public static function isModuleExists($path, $moduleName)
    {
        $moduleExist = exec(
            sprintf(
                'cd %s && php bin/magento module:status | grep %s',
                $path,
                $moduleName
            )
        );

        return !$moduleExist ? false : true;
    }
}