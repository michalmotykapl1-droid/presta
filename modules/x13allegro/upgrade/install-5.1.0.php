<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_5_1_0($module)
{
    // generate new class_index
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_carrier`
            ADD `id_operator`   int(10) NOT NULL DEFAULT -1,
            ADD `operator_name` char(32) NULL'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_carrier_package_info`
            ADD `tracking_number` char(64) NULL,
            ADD `send` tinyint(1) NOT NULL DEFAULT 0,
            ADD INDEX (`send`)'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_form`
            ADD `id_allegro_shipment` int(10) unsigned NOT NULL AFTER `id_allegro_form`,
            ADD INDEX (`id_allegro_shipment`)'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_auction`
            ADD `price_buy_now` decimal(10,2) NOT NULL AFTER `quantity_start`'
    );

    foreach (array(
         'ORDER_ALLEGRO_SEND_SHIPPING' => 1,
         'QUANITY_ALLEGRO_HOOK_SKIP' => 0
     ) as $key => $conf) {
        XAllegroConfiguration::updateValue($key, $conf);
    }

    $module->reinstallTabs();

    return true;
}
