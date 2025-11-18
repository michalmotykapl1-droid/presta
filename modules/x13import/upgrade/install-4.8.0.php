<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @param Module $module
 * @return bool
 */
function upgrade_module_4_8_0($module)
{
    // reinstall deprecated hooks
    $module->unregisterHook('backOfficeHeader');
    $module->registerHook('displayBackOfficeHeader');

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_item`
            ADD `upc` varchar(12) NOT NULL DEFAULT "" AFTER `mpn`,
            ADD `show_condition` tinyint(1) NOT NULL DEFAULT 0 AFTER `condition`,
            ADD `location` varchar(64) NOT NULL DEFAULT "" AFTER `out_of_stock`,
            ADD `low_stock_alert` tinyint(1) NOT NULL DEFAULT 0 AFTER `out_of_stock`,
            ADD `low_stock_threshold` int(10) unsigned NULL AFTER `out_of_stock`,
            ADD `force_product_id` int(10) unsigned NULL AFTER `images_data`,
            ADD `external_data` text NOT NULL DEFAULT "" AFTER `auction_id`,
            ADD `accessories` text NOT NULL DEFAULT "" AFTER `tags`'
    );

    // "active" DEFAULT 1 on upgrade
    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_price`
            ADD `active` tinyint(1) NOT NULL DEFAULT 1 AFTER `override_category`,
            ADD INDEX(`active`)'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_product`
            ADD `exclude_quantity` tinyint(1) NOT NULL DEFAULT 0 AFTER `exclude_price`,
            ADD INDEX(`exclude_quantity`)'
    );

    return true;
}
