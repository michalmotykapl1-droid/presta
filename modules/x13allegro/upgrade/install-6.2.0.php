<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Api\Adapter\Delivery\CarrierOperators;
use x13allegro\SyncManager\Order\Data\Model\OrderMessage;
use x13allegro\Adapter\DbAdapter;

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_6_2_0($module)
{
    $dbCharset = DbAdapter::getUtf8Collation();

    // generate new class_index
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_customer_address` (
            `id_xallegro_customer_address`  int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_customer`                   int(10) unsigned NOT NULL,
            `id_address`                    int(10) unsigned NOT NULL,
            `hash`                          char(64) NOT NULL,

            PRIMARY KEY (`id_xallegro_customer_address`),
            UNIQUE KEY (`id_xallegro_customer_address`, `id_customer`, `id_address`),
            INDEX (`hash`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_order` (
            `id_xallegro_order`         int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_xallegro_account`       int(10) unsigned NOT NULL,
            `id_order`                  int(10) unsigned NULL,
            `delivery_method`           char(64) NOT NULL,
            `checkout_form`             char(64) NOT NULL,
            `checkout_form_content`     text NULL,
            `event_type`                char(32) NOT NULL,
            `occurred_at`               char(32) NOT NULL,
            `webapi`                    tinyint(1) NOT NULL DEFAULT 0,

            PRIMARY KEY (`id_xallegro_order`),
            UNIQUE KEY (`id_xallegro_order`, `id_xallegro_account`),
            INDEX (`delivery_method`),
            INDEX (`checkout_form`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_order_item` (
            `id_xallegro_order`         int(10) unsigned NOT NULL,
            `line_item`                 char(64) NOT NULL,

            UNIQUE KEY (`id_xallegro_order`, `line_item`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_order_status` (
            `allegro_status`            char(32) NULL,
            `id_order_state`            int(10) unsigned NULL,
            
            INDEX(`allegro_status`),
            INDEX(`id_order_state`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_delivery_rate` (
            `id_xallegro_delivery_rate` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_xallegro_account`       int(10) unsigned NOT NULL,
            `shipping_rate`             char(64) NOT NULL,
            `position`                  int(10) unsigned NOT NULL,
            `default`                   tinyint(1) NOT NULL,
            `active`                    tinyint(1) NOT NULL,

            PRIMARY KEY (`id_xallegro_delivery_rate`),
            UNIQUE KEY (`id_xallegro_delivery_rate`, `id_xallegro_account`),
            INDEX (`shipping_rate`),
            INDEX (`position`),
            INDEX (`default`),
            INDEX (`active`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ .'xallegro_account`
            DROP `last_point`,
            DROP `last_deals_point`,
            DROP `session`,
            DROP `expire_session`,
            ADD `last_offer_event` char(32) NULL,
            ADD `last_order_event` char(32) NULL'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ .'xallegro_carrier`
            CHANGE `id_operator` `id_operator` char(32) NULL'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ .'xallegro_carrier_package_info`
            CHANGE `id_operator` `id_operator` char(32) NULL'
    );

    Db::getInstance()->execute('CREATE TABLE `' . _DB_PREFIX_ . 'xallegro_form_backup` LIKE `' . _DB_PREFIX_ . 'xallegro_form`');
    Db::getInstance()->execute('INSERT `' . _DB_PREFIX_ . 'xallegro_form_backup` SELECT * FROM `' . _DB_PREFIX_ . 'xallegro_form`');

    $authColumnExists = Db::getInstance()->executeS('
       SELECT COUNT(*) as `count`
       FROM information_schema.columns
       WHERE table_schema = "' . _DB_NAME_ . '"
           AND table_name = "' . _DB_PREFIX_ . 'xallegro_account"
            AND column_name = "expire_authorization"'
    );

    if (isset($authColumnExists[0]) && !(int)$authColumnExists[0]['count']) {
        Db::getInstance()->execute('
            ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_account`
            ADD `expire_authorization` datetime NOT NULL DEFAULT "2016-01-01 00:00:00"'
        );
    }

    $carriers = Db::getInstance()->executeS('
        SELECT `id_fields_shipment`, `id_operator` 
        FROM `' . _DB_PREFIX_ . 'xallegro_carrier`'
    );

    if ($carriers) {
        foreach ($carriers as $carrier) {
            $newId = CarrierOperators::map($carrier['id_operator']);
            Db::getInstance()->execute('
                UPDATE `' . _DB_PREFIX_ . 'xallegro_carrier`
                SET `id_operator` = "' . pSQL($newId) . '"
                WHERE `id_fields_shipment` = "' . pSQL($carrier['id_fields_shipment']) . '"'
            );
        }
    }

    $carriersInfo = Db::getInstance()->executeS('
        SELECT `id_order_carrier`, `id_operator` 
        FROM `' . _DB_PREFIX_ . 'xallegro_carrier_package_info`'
    );

    if ($carriersInfo) {
        foreach ($carriersInfo as $carrierInfo) {
            $newId = CarrierOperators::map($carrierInfo['id_operator']);
            Db::getInstance()->execute('
                UPDATE `' . _DB_PREFIX_ . 'xallegro_carrier_package_info`
                SET `id_operator` = "' . pSQL($newId) . '"
                WHERE `id_order_carrier` = "' . pSQL($carrierInfo['id_order_carrier']) . '"'
            );
        }
    }

    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ . 'order_state`
        SET `deleted` = 1
        WHERE `id_order_state` IN (' . implode(',', [
            XAllegroConfiguration::get('PAYU_STATUS_INSTALLMENT'),
            XAllegroConfiguration::get('PAYU_STATUS_SELECTED'),
            XAllegroConfiguration::get('PAYU_STATUS_AWAITING'),
            XAllegroConfiguration::get('ALLEGRO_STATUS_SENDIT'),
            XAllegroConfiguration::get('ALLEGRO_STATUS_UNSET')
        ]) .')'
    );

    XAllegroConfiguration::updateValue('ORDER_MESSAGE_CONFIGURATION', json_encode([OrderMessage::BUYER_MESSAGE => true]));
    XAllegroConfiguration::updateValue('ORDER_IMPORT_UNASSOC_PRODUCTS', 1);
    XAllegroConfiguration::updateValue('ORDER_IMPORT_UNASSOC_SUMMARY', 1);

    if ((int)XAllegroConfiguration::get('IMPORT_ORDERS_CHUNK') < 50) {
        XAllegroConfiguration::updateValue('IMPORT_ORDERS_CHUNK', 50);
    }

    $module->reinstallTabs();
    $module->registerHook('displayAdminOrderLeft');

    return true;
}
