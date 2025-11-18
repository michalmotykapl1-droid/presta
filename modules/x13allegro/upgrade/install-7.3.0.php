<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Adapter\DbAdapter;
use x13allegro\Api\Model\Order\Enum\FulfillmentStatus;

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_7_3_0($module)
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    $dbCharset = DbAdapter::getUtf8Collation();

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_order_status`
            RENAME `' . _DB_PREFIX_ . 'xallegro_order_status_backup`'
    );
    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_order_status` (
            `allegro_status`            char(32) NULL,
            `marketplace`               char(32) NULL,
            `id_order_state`            int(10) unsigned NULL,

            UNIQUE KEY (`allegro_status`, `marketplace`),
            INDEX (`allegro_status`),
            INDEX (`marketplace`),
            INDEX (`id_order_state`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_account`
            ADD `id_language` int(10) unsigned NULL AFTER `base_marketplace`,
            ADD INDEX(`id_language`)'
    );
    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ . 'xallegro_account`
            SET `id_language` = ' . (int)XAllegroConfiguration::get('AUCTION_LANGUAGE')
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_auction`
            ADD `auto_renew` tinyint(1) NULL AFTER `start_time`,
            ADD `end_date` datetime NULL AFTER `archived_date`,
            ADD INDEX(`auto_renew`),
            ADD INDEX(`end_date`)'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_custom_product`
            ADD `auto_renew` tinyint(1) NULL AFTER `sync_quantity_allegro`'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_auction_process`
            MODIFY `operation` char(255) NOT NULL,
            DROP INDEX operation'
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_log` (
            `id_xallegro_log`           char(64) NOT NULL,
            `id_xallegro_account`       int(10) unsigned NULL,
            `id_employee`               int(10) unsigned NULL,
            `id_offer`                  bigint(20) unsigned NULL,
            `id_shop`                   int(10) unsigned NULL,
            `id_product`                int(10) unsigned NULL,
            `id_product_attribute`      int(10) unsigned NULL,
            `id_order`                  int(10) unsigned NULL,
            `env`                       char(64) NOT NULL,
            `level`                     char(64) NOT NULL,
            `type`                      char(64) NOT NULL,
            `message`                   text NULL,
            `hash`                      char(32) NOT NULL,
            `counter`                   int(10) unsigned NOT NULL DEFAULT 1,
            `displayed`                 tinyint(1) unsigned NOT NULL DEFAULT 0,
            `send`                      tinyint(1) unsigned NOT NULL DEFAULT 1,
            `last_occurrence`           datetime NOT NULL,

            PRIMARY KEY (`id_xallegro_log`),
            INDEX (`id_xallegro_account`),
            INDEX (`id_employee`),
            INDEX (`id_shop`),
            INDEX (`id_product`),
            INDEX (`id_product_attribute`),
            INDEX (`id_order`),
            INDEX (`env`),
            INDEX (`level`),
            INDEX (`type`),
            UNIQUE INDEX (`hash`, `displayed`),
            INDEX (`send`),
            INDEX (`last_occurrence`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    $statusBackup = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'xallegro_order_status_backup`');
    if (!empty($statusBackup)) {
        $statusNew = [];

        foreach ($statusBackup as $status) {
            $statusNew['default'][$status['allegro_status']]['id_order_state'] = $status['id_order_state'];
        }

        XAllegroStatus::updateStatuses($statusNew, false);
    }

    $productizationMode = XAllegroConfiguration::get('PRODUCTIZATION_MODE');
    if ($productizationMode == XAllegroAuction::PRODUCTIZATION_NONE) {
        XAllegroConfiguration::updateValue('PRODUCTIZATION_MODE', XAllegroAuction::PRODUCTIZATION_NEW);
    }

    $priceUpdateChunk = (int)XAllegroConfiguration::get('PRICE_UPDATE_CHUNK');
    $quantityUpdateChunk = (int)XAllegroConfiguration::get('QUANITY_ALLEGRO_UPDATE_CHUNK');
    $updateOfferChunk = max($priceUpdateChunk, $quantityUpdateChunk, X13_ALLEGRO_UPDATE_OFFERS_CHUNK);

    XAllegroConfiguration::updateValue('UPDATE_OFFERS_CHUNK', $updateOfferChunk);
    XAllegroConfiguration::updateValue('PRODUCT_ASSOC_CLOSE_UNACTIVE_DB', 0);
    XAllegroConfiguration::updateValue('PRODUCT_ASSOC_CLOSE_SKIP_BID_AUCTION', 0);
    XAllegroConfiguration::updateValue('ORDER_STATUS_BY_MARKETPLACE', 0);
    XAllegroConfiguration::updateValue('ORDER_IMPORT_UNASSOC_PRODUCTS_EXTERNAL', 0);
    XAllegroConfiguration::updateValue('ORDER_ALLEGRO_SHIPPING_STATUS_FULFILLMENT', FulfillmentStatus::SENT()->getKey());
    XAllegroConfiguration::updateValue('QUANTITY_AUTO_RENEW', 0);
    XAllegroConfiguration::updateValue('QUANTITY_AUTO_RENEW_THRESHOLD', 0);
    XAllegroConfiguration::updateValue('PRODUCT_ASSOC_RENEW_ONLY_ACTIVE', 1);
    XAllegroConfiguration::updateValue('PRODUCT_ASSOC_RENEW_ACTIVE', 0);
    XAllegroConfiguration::updateValue('PRODUCT_ASSOC_RENEW_ACTIVE_DB', 0);
    XAllegroConfiguration::updateValue('OFFER_RENEW_KEEP_PROMOTION', 1);
    XAllegroConfiguration::updateValue('OFFER_RENEW_MAX_DAYS', 0);
    XAllegroConfiguration::updateValue('LOG_SEND', 0);
    XAllegroConfiguration::updateValue('LOG_SEND_LEVEL', json_encode([]));
    XAllegroConfiguration::updateValue('LOG_SEND_EMAIL_LIST', json_encode([]));

    // new "global-options" in ConfigurationAccount for all Accounts
    foreach (XAllegroAccount::getAllIds(false) as $row) {
        $config = new XAllegroConfigurationAccount($row['id_xallegro_account']);
        $config->updateValue('QUANTITY_AUTO_RENEW', XAllegroConfigurationAccount::GLOBAL_OPTION);
        $config->updateValue('OFFER_RENEW_KEEP_PROMOTION', XAllegroConfigurationAccount::GLOBAL_OPTION);
    }

    $module->reinstallTabs();

    $emptyProductId = (int)XAllegroConfiguration::get('EMPTY_PRODUCT_ID');
    if ($emptyProductId) {
        $productFieldsNotNull = [];
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $productFieldsNotNull['isbn'] = '';
        }
        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            $productFieldsNotNull['mpn'] = '';
        }

        Db::getInstance()->update('product', array_merge($productFieldsNotNull, [
            'ean13' => '',
            'upc' => ''
        ]), 'id_product = ' . $emptyProductId);
    }

    return true;
}
