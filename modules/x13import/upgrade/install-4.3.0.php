<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @param $module x13import
 * @return bool
 */
function upgrade_module_4_3_0($module)
{
    return Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_item`
        ADD `isbn` varchar(32) AFTER ean13'
    );
}
