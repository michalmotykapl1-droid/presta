<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Adapter\DbAdapter;

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_6_4_0($module)
{
    $dbCharset = DbAdapter::getUtf8Collation();
    
    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_custom_price` (
            `id_xallegro_account` int(10) unsigned NOT NULL DEFAULT 0,
            `id_product` int(10) unsigned NOT NULL,
            `id_product_attribute` int(10) unsigned NOT NULL DEFAULT 0,
            `value` DECIMAL(20, 6) NOT NULL,
            `method` ENUM("price", "amount", "percentage") NOT NULL DEFAULT "price",
            INDEX (`id_xallegro_account`, `id_product`, `id_product_attribute`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    $module->reinstallTabs();

    return true;
}
