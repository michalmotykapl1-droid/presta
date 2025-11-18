<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Api\Adapter\Delivery\DeliveryMethods;
use x13allegro\Api\Adapter\Location\CountryCode;
use x13allegro\Api\Adapter\Location\Province;
use x13allegro\Api\Adapter\Payments\Invoice;
use x13allegro\Api\Adapter\Delivery\HandlingTime;
use x13allegro\Api\Adapter\Publication\Duration;
use x13allegro\Adapter\DbAdapter;

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_6_0_0($module)
{
    $dbCharset = DbAdapter::getUtf8Collation();

    // generate new class_index
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_account`
            DROP `verkey`,
            DROP `vercats`'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_carrier`
            DROP PRIMARY KEY,
            MODIFY `id_fields_shipment` char(64) NOT NULL,
            ADD INDEX(`id_fields_shipment`)'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_auction`
            DROP `quantity_start`,
            DROP `allegro_shop`,
            DROP `abroad`,
            DROP `invoice`,
            DROP `bank_transfer`,
            DROP `pas`,
            DROP `state`,
            DROP `city`,
            DROP `zipcode`,
            DROP `id_allegro_category`,
            DROP `category_fields`,
            DROP `item`,
            DROP `shipping_cost`,
            DROP `shipment`,
            DROP `prepare_time`,
            DROP `details`,
            DROP `default`,
            DROP `resume_auction`'
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_delivery` (
            `id_xallegro_delivery`      int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name`                      char(64) NOT NULL,
            `city`                      char(64) NOT NULL,
            `country_code`              char(2) NOT NULL,
            `post_code`                 char(16) NULL,
            `province`                  char(32) NULL,
            `invoice`                   char(16) NOT NULL,
            `handling_time`             char(8) NOT NULL,
            `additional_info`           text NULL,
            `default`                   tinyint(1) NOT NULL,
            `active`                    tinyint(1) NOT NULL,

            PRIMARY KEY (`id_xallegro_delivery`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_category` RENAME `' . _DB_PREFIX_ . 'xallegro_category_backup`');

    Db::getInstance()->execute('
        CREATE TABLE `' . _DB_PREFIX_ . 'xallegro_category` (
            `id_xallegro_category`      int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_allegro_category`       int(10) unsigned NULL,
            `id_categories`             char(255) NULL,
            `path`                      char(255) NOT NULL,
            `fields_values`             longtext NULL,
            `fields_mapping`            longtext NULL,
            `tags`                      text NULL,

            PRIMARY KEY (`id_xallegro_category`),
            KEY (`id_allegro_category`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=' . $dbCharset
    );

    // START carriers - migration --------------------------------------------------------------------------------------
    $carriers = Db::getInstance()->executeS('
        SELECT `id_fields_shipment` FROM `' . _DB_PREFIX_ . 'xallegro_carrier`'
    );

    if ($carriers) {
        foreach ($carriers as $carrier) {
            $newId = DeliveryMethods::map($carrier['id_fields_shipment']);
            if ($newId) {
                Db::getInstance()->execute('
                    UPDATE `' . _DB_PREFIX_ . 'xallegro_carrier`
                    SET `id_fields_shipment` = "' . pSQL($newId) . '"
                    WHERE `id_fields_shipment` = ' . (int)$carrier['id_fields_shipment']
                );
            }
        }
    }
    // END carriers - migration ----------------------------------------------------------------------------------------

    // START pas - migration --------------------------------------------------------------------------------------
    $profiles = Db::getInstance()->executeS('
        SELECT * FROM `' . _DB_PREFIX_ . 'xallegro_pas`'
    );

    if ($profiles) {
        foreach ($profiles as $profile) {
            $countryCode = CountryCode::map((int)$profile['country']);
            $handlingTime = HandlingTime::map((int)$profile['prepare_time']);
            $province = Province::map((int)$profile['state']);
            $invoice = Invoice::map((int)$profile['invoice']);

            $pas = new XAllegroPas();
            $pas->name = $profile['name'];
            $pas->city = $profile['city'];
            $pas->country_code = ($countryCode ? $countryCode : 'PL');
            $pas->post_code = $profile['zipcode'];
            $pas->province = ($province ? $province : 0);
            $pas->invoice = ($invoice ? $invoice : 0);
            $pas->handling_time = ($handlingTime ? $handlingTime : 0);
            $pas->additional_info = $profile['details'];
            $pas->default = $profile['default'];
            $pas->active = $profile['active'];
            $pas->save();
        }
    }

    // END pas - migration ----------------------------------------------------------------------------------------

    Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'xallegro_pas`');
    Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'xallegro__cats_list`');
    Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'xallegro__cats_form_fields`');
    Db::getInstance()->execute('TRUNCATE `' . _DB_PREFIX_ . 'xallegro_category`');

    XAllegroConfiguration::updateValue('DURATION_DEFAULT', Duration::map((int)XAllegroConfiguration::get('DURATION_DEFAULT')));

    $collection = new PrestaShopCollection('XAllegroAccount');
    $accounts = $collection->getResults();

    if ($accounts) {
        /** @var XAllegroAccount $account */
        foreach ($accounts as $account) {
            $account->client_id = '*';
            $account->client_secret = '*';
            $account->access_token = null;
            $account->refresh_token = null;
            $account->session = [];
            $account->expire_authorization = '0000-00-00 00:00:00';
            $account->expire_refresh = '0000-00-00 00:00:00';
            $account->expire_session = '0000-00-00 00:00:00';
            $account->save();
        }
    }

    $module->reinstallTabs();

    return true;
}
