<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Adapter\DbAdapter;

/**
 * @return bool
 */
function upgrade_module_7_0_1()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    Db::getInstance()->execute(
        'ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_auction`
            ADD `selling_mode` ENUM("BUY_NOW", "AUCTION") DEFAULT NULL AFTER `id_product_attribute`'
    );

    Db::getInstance()->execute(
        'ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_carrier_package_info`
            ADD `send_enabled` tinyint(1) NOT NULL DEFAULT 1 AFTER `send`,
            ADD `error` int(10) unsigned NOT NULL DEFAULT 0 AFTER `send_enabled`'
    );

    $auctionNbRows = DbAdapter::countTableEntries('xallegro_auction', 'id_xallegro_auction');

    if (!DbAdapter::showColumnIndex('xallegro_auction', 'quantity') && $auctionNbRows < 35000) {
        Db::getInstance()->execute('
            ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_auction`
                ADD INDEX(`quantity`)'
        );
    }
    if (!DbAdapter::showColumnIndex('xallegro_auction', 'selling_mode') && $auctionNbRows < 35000) {
        Db::getInstance()->execute('
            ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_auction`
                ADD INDEX(`selling_mode`)'
        );
    }

    $carrierPackageInfoNbRows = DbAdapter::countTableEntries('xallegro_carrier_package_info', 'id_order_carrier');

    if (!DbAdapter::showColumnIndex('xallegro_carrier_package_info', 'send_enabled') && $carrierPackageInfoNbRows < 75000) {
        Db::getInstance()->execute('
            ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_carrier_package_info`
                ADD INDEX(`send_enabled`)'
        );
    }
    if (!DbAdapter::showColumnIndex('xallegro_carrier_package_info', 'error') && $carrierPackageInfoNbRows < 75000) {
        Db::getInstance()->execute('
            ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_carrier_package_info`
                ADD INDEX(`error`)'
        );
    }

    XAllegroConfiguration::updateValue('PRODUCTIZATION_MODE', XAllegroAuction::PRODUCTIZATION_ASSIGN);

    return true;
}
