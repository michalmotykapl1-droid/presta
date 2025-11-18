<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @return bool
 */
function upgrade_module_4_7_3()
{
    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_category` 
            ADD `markup_type` ENUM("percent", "amount") NOT NULL DEFAULT "percent" AFTER `markup`'
    );

    return true;
}
