<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @param $module x13import
 * @return bool
 */
function upgrade_module_4_3_1($module)
{
    return Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_tmp`
        ADD `max_out_of_stock` tinyint(1) UNSIGNED AFTER quantity,
        ADD `min_out_of_stock` tinyint(1) UNSIGNED AFTER quantity'
    );
}
