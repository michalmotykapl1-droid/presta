<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @param $module x13import
 * @return bool
 */
function upgrade_module_4_3_5($module)
{
    return Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_item`
            ADD `visibility_num` tinyint(1) NOT NULL DEFAULT 1 AFTER `visibility`,
            ADD `auction_id` bigint(20) NOT NULL DEFAULT 0'
    );
}
