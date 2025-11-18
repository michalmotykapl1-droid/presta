<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Adapter\DbAdapter;
use x13allegro\Api\XAllegroApi;

/**
 * @return bool
 */
function upgrade_module_7_2_0()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    $dbCharset = DbAdapter::getUtf8Collation();

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_configuration`
            MODIFY `name` char(64)'
    );
    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_configuration_account`
            MODIFY `name` char(64)'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_account`
            ADD `base_marketplace` char(32) NULL AFTER `sandbox`,
            ADD INDEX(`base_marketplace`)'
    );
    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ . 'xallegro_account`
            SET `base_marketplace` = "' . pSQL(XAllegroApi::MARKETPLACE_PL) . '"'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_order`
            ADD `marketplace` char(32) NOT NULL AFTER `fulfillment_status`,
            ADD INDEX(`marketplace`)'
    );
    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ . 'xallegro_order`
            SET `marketplace` = "' . pSQL(XAllegroApi::MARKETPLACE_PL) . '"'
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_auction_marketplace` (
            `id_auction`                    bigint(20) NOT NULL,
            `marketplace`                   char(32) NOT NULL,
            `price_buy_now`                 decimal(10,2) NOT NULL DEFAULT "0.00",
            `last_status`                   char(32) NULL,
            `last_status_date`              datetime NULL,
            `last_status_refusal_reasons`   text NULL,

            PRIMARY KEY (`id_auction`, `marketplace`),
            INDEX (`marketplace`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_auction_process` (
            `id_xallegro_auction_process`   int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_xallegro_account`           int(10) unsigned NOT NULL,
            `id_auction`                    bigint(20) NOT NULL,
            `id_operation`                  char(64) NOT NULL,
            `operation`                     ENUM("PRICE_UPDATE") NOT NULL,
            `date_add`                      datetime NOT NULL,

            PRIMARY KEY (`id_xallegro_auction_process`),
            INDEX (`id_xallegro_account`),
            INDEX (`id_auction`),
            INDEX (`operation`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    XAllegroConfiguration::updateValue('AUCTION_FIELDS_LIST_SETTINGS', json_encode([]));
    XAllegroConfiguration::updateValue('AUCTION_MARKETPLACE_CONVERSION_RATE', 'CURRENCY');
    XAllegroConfiguration::updateValue('AUCTION_MARKETPLACE_CONVERSION_RATE_VALUE', json_encode([]));
    XAllegroConfiguration::updateValue('DELETE_ARCHIVED_OFFERS', 90);

    return true;
}
