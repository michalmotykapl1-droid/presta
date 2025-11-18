<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Adapter\DbAdapter;
use x13allegro\Repository\ProductCustomRepository;

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_6_7_1($module)
{
    $dbCharset = DbAdapter::getUtf8Collation();

    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    $languageExpected = Language::getIdByIso('PL');
    $languages = Language::getLanguages();

    if (false !== ($key = array_search($languageExpected, array_column($languages, 'id_lang')))) {
        $languageId = (int)$languages[$key]['id_lang'];
    } else {
        $languageId = (int)Configuration::get('PS_LANG_DEFAULT');
    }

    XAllegroConfiguration::updateValue('AUCTION_LANGUAGE', $languageId);
    XAllegroConfiguration::updateValue('IMAGES_CACHE', 168);
    XAllegroConfiguration::updateValue('INACTIVE_PRODUCTS_SKIP', 0);
    XAllegroConfiguration::updateValue('ORDER_ALLEGRO_SHIPPING_STATUS', 1);
    XAllegroConfiguration::updateValue('REGISTER_CUSTOMER_GROUP', (int)Configuration::get('PS_CUSTOMER_GROUP'));

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_custom_product` (
            `id_xallegro_account`           int(10) unsigned NOT NULL DEFAULT 0,
            `id_product`                    int(10) unsigned NOT NULL,
            `title_pattern`                 char(128) NULL,
            `sync_price`                    tinyint(1) NULL,
            `sync_quantity_allegro`         tinyint(1) NULL,

            UNIQUE KEY (`id_xallegro_account`, `id_product`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    $productCustomData = Db::getInstance()->executeS('
        SELECT
            `id_product`,
            `title_pattern` as `titlePattern`,
            IF(`sync_price` = 4, NULL, `sync_price`) as `syncPrice`,
            `sync_quantity_allegro` as `syncQuantityAllegro`,
            0 as `id_xallegro_account`
        FROM `' . _DB_PREFIX_ . 'xallegro_product`
        WHERE `id_product_attribute` = 0'
    );

    $migrationResult = true;
    if ($productCustomData) {
        foreach ($productCustomData as $data) {
            $migrationResult &= ProductCustomRepository::update((int)$data['id_product'], (int)$data['id_xallegro_account'], array_merge($data, ['prices' => []]));
        }
    }

    if ($migrationResult) {
        Db::getInstance()->execute('
            ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_product`
                DROP `title_pattern`,
                DROP `sync_price`,
                DROP `sync_quantity_allegro`'
        );
    }

    // new "global-options" in ConfigurationAccount for all Accounts
    foreach (XAllegroAccount::getAllIds(false) as $row) {
        $config = new XAllegroConfigurationAccount($row['id_xallegro_account']);
        $config->updateValue('PRICE_UPDATE', XAllegroConfigurationAccount::GLOBAL_OPTION);
        $config->updateValue('QUANITY_ALLEGRO_UPDATE', XAllegroConfigurationAccount::GLOBAL_OPTION);
    }

    $module->registerHook('actionOrderHistoryAddAfter');
    $module->reinstallTabs();

    if ($module->removeOverride('OrderHistory')) {
        $module->addOverride('OrderHistory');
    }

    return true;
}
