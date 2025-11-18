<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

use x13import\Adapter\DbAdapter;
use x13import\Wholesaler\WholesalerConfiguration;

/**
 * @param $module x13import
 * @return bool
 */
function upgrade_module_4_4_0($module)
{
    // generate new class_index
    XImportAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    $module->reinstallTabs();

    $dbCharset = DbAdapter::getUtf8Collation();

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_configuration_wholesaler` (
            `wholesaler` varchar(64) NOT NULL,
            `value` text NOT NULL,
            
            PRIMARY KEY (`wholesaler`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_attachment` (
            `id_ximport_attachment`     int(10) unsigned NOT NULL AUTO_INCREMENT,
            `code`                      varchar(128) NOT NULL,
            `id_product`                int(10) unsigned NOT NULL,
            `id_attachment`             int(10) unsigned NOT NULL,
            `fingerprint`               varchar(32) NOT NULL,
            
            PRIMARY KEY (`id_ximport_attachment`),
            INDEX (`code`),
            INDEX (`id_product`),
            INDEX (`id_attachment`),
            INDEX (`fingerprint`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_item_attachment` (
            `id_ximport_item_attachment` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `code`                      varchar(128) NOT NULL,
            `type`                      varchar(10) NOT NULL,
            `value`                     mediumtext NOT NULL,
            `value_md5`                 varchar(32) NOT NULL,
            `fingerprint`               varchar(32) NULL,
            
            PRIMARY KEY (`id_ximport_item_attachment`),
            UNIQUE INDEX (`code`, `value_md5`),
            INDEX (`code`),
            INDEX (`value_md5`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_item`
            ADD `unit_price` decimal(20,6) NOT NULL DEFAULT "0.00" AFTER `price`,
            ADD `mpn` varchar(40) NOT NULL DEFAULT "" AFTER `ean13`,
            ADD `is_virtual` tinyint(1) NOT NULL DEFAULT 0 AFTER `on_sale`,
            ADD `additional_shipping_cost` decimal(20,6) NOT NULL DEFAULT "0.00" AFTER `wholesale_price`,
            ADD `delivery_in_stock` varchar(255) NOT NULL DEFAULT "" AFTER `available_later`,
            ADD `delivery_out_stock` varchar(255) NOT NULL DEFAULT "" AFTER `available_later`'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_tmp`
            ADD `product_unit_price` decimal(20,6) NOT NULL DEFAULT "0.00" AFTER `max_out_of_stock`,
            ADD `basic_unit_price` decimal(20,6) NOT NULL DEFAULT "0.00" AFTER `basic_price`'
    );

    if ($dbCharset == 'utf8mb4') {
        Db::getInstance()->execute('
            ALTER TABLE `' . _DB_PREFIX_ . 'ximport_item` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'
        );
    }

    $configuration = [];
    $configurationDefault = array_keys(XImportConfiguration::getDefaultConfiguration());
    $configurationTable = Db::getInstance()->executeS('
        SELECT `name`, `value`
        FROM `' . _DB_PREFIX_ . 'ximport_configuration`'
    );

    foreach ($configurationTable as $row) {
        $configuration[$row['name']] = $row['value'];
    }

    foreach (XImportWholesalers::getWholesalers() as $wholesaler) {
        $wholesalerOptions = new WholesalerConfiguration($wholesaler['name']);

        /** @var XImportWholesalers $className */
        $className = XImportWholesalers::getWholesalerClassName($wholesaler['name']);
        $wholesalerCustomFields = array_column($className::getCustomFields(), 'name');

        // default configuration
        foreach ($wholesalerCustomFields as $customField) {
            if (isset($configuration[$customField])) {
                $wholesalerOptions->{$customField} = $configuration[$customField];
            }
        }

        // advanced configuration
        if (isset($configuration['ADVANCED_CARRIERS']) && $configuration['ADVANCED_CARRIERS']) {
            $wholesalerOptions->ADDITIONAL_OPTION_ENABLED = 1;
        }
        if ((isset($configuration['ADVANCED_AFTER_CRON_BLOCK']) && $configuration['ADVANCED_AFTER_CRON_BLOCK'])
            || (isset($configuration['ADVANCED_STOCK']) && $configuration['ADVANCED_STOCK'])
        ) {
            $wholesalerOptions->IMPORT_RESTRICTION_ENABLED = 1;
        }

        // individual advanced configuration
        if (isset($configuration[$wholesaler['name'] . '_ADV_STOCK']) && $configuration[$wholesaler['name'] . '_ADV_STOCK']) {
            $wholesalerOptions->IMPORT_RESTRICTION_STOCK_ZERO = (int)$configuration[$wholesaler['name'] . '_ADV_STOCK'];
        }
        if (isset($configuration[$wholesaler['name'] . '_ADV_CRON_BLOCK']) && $configuration[$wholesaler['name'] . '_ADV_CRON_BLOCK']) {
            $wholesalerOptions->IMPORT_RESTRICTION_CRON_BLOCK = (int)$configuration[$wholesaler['name'] . '_ADV_CRON_BLOCK'];
        }
        if (isset($configuration[$wholesaler['name'] . '_ADV_CARRIERS']) && $configuration[$wholesaler['name'] . '_ADV_CARRIERS']) {
            $wholesalerOptions->ADDITIONAL_OPTION_CARRIERS = explode(',', $configuration[$wholesaler['name'] . '_ADV_CARRIERS']);
        }
    }

    // remove unnecessary configuration values from database
    foreach (array_keys($configuration) as $conf) {
        if (!in_array($conf, $configurationDefault)) {
            XImportConfiguration::deleteByName($conf);
        }
    }

    $newConfig = [
        'USE_TOKEN' => 0,
        'TOKEN' => Tools::passwdGen()
    ];
    foreach ($newConfig as $conf => $value) {
        XImportConfiguration::updateValue($conf, $value);
    }

    return true;
}