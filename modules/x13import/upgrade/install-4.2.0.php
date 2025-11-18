<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @param $module x13import
 * @return bool
 */
function upgrade_module_4_2_0($module)
{
    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_item`
        ADD `images_data` TEXT NOT NULL'
    );

    return true;
}
