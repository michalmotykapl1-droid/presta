<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Api\XAllegroApi;
use x13allegro\Api\XAllegroApiTools;
use x13allegro\Adapter\DbAdapter;

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_5_0_0($module)
{
    $dbCharset = DbAdapter::getUtf8Collation();

    // generate new class_index
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    // START shipments - migration -------------------------------------------------------------------------------------
    $oldShipmentsExists = (int)Db::getInstance()->getValue('
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = "' . _DB_NAME_ . '"
            AND table_name = "' . _DB_PREFIX_ . 'xallegro__sell_form_fields"'
    );

    if ($oldShipmentsExists) {
        $fidsDb = Db::getInstance()->executeS('
            SELECT `sell_form_id`, `sell_form_field_desc`
            FROM `' . _DB_PREFIX_ . 'xallegro__sell_form_fields`
            WHERE `sell_form_id` > 35
                AND `sell_form_id` < 136
                AND `sandbox` = 0'
        );
    }

    if ($oldShipmentsExists && !empty($fidsDb)) {
        $shipments = Db::getInstance()->executeS('
            SELECT `id_xallegro_pas`, `shipments`
            FROM `' . _DB_PREFIX_ . 'xallegro_pas`'
        );
        $fidsDb = Db::getInstance()->executeS('
            SELECT `sell_form_id`, `sell_form_field_desc`
            FROM `' . _DB_PREFIX_ . 'xallegro__sell_form_fields`
            WHERE `sell_form_id` > 35
                AND `sell_form_id` < 136
                AND `sandbox` = 0'
        );

        $fids = array();
        foreach ($fidsDb as $fid) {
            $fids[$fid['sell_form_id']] = $fid['sell_form_field_desc'];
        }

        foreach ($shipments as $shipment) {
            $shipmentData = unserialize($shipment['shipments']);
            $shipmentNew = array();

            foreach ($shipmentData as $fidId => $data) {
                if ($fidId == 35) {
                    foreach ($data as $fidOption) {
                        $shipmentNew[35 . '_' . $fidOption] = array(
                            'enabled' => $fidOption
                        );
                    }
                }

                if (isset($fids[$fidId])) {
                    $shipmentNew[$fids[$fidId]] = $data;
                }
            }

            Db::getInstance()->execute('
                UPDATE `' . _DB_PREFIX_ . 'xallegro_pas`
                SET `shipments` = "' . pSQL(json_encode($shipmentNew)) . '"
                WHERE `id_xallegro_pas` = ' . (int)$shipment['id_xallegro_pas']
            );
        }
    }
    // END shipments - migration ---------------------------------------------------------------------------------------

    // START category fields - migration -------------------------------------------------------------------------------
    $categoryFields = Db::getInstance()->executeS('
        SELECT `id_xallegro_category`, `fields`
        FROM `' . _DB_PREFIX_ . 'xallegro_category`'
    );

    foreach ($categoryFields as $categoryField) {
        if (!XAllegroApiTools::isSerialized($categoryField['fields'])) {
            continue;
        }

        Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'xallegro_category`
            SET `fields` = "' . pSQL(json_encode(unserialize($categoryField['fields']))) . '"
            WHERE `id_xallegro_category` = ' . (int)$categoryField['id_xallegro_category']
        );
    }
    // END category fields - migration ---------------------------------------------------------------------------------

    // START individual product properties - migration -----------------------------------------------------------------
    Db::getInstance()->execute(
        '
        DELETE p1 FROM `' . _DB_PREFIX_ . 'xallegro_product` p1
        INNER JOIN `' . _DB_PREFIX_ . 'xallegro_product` p2
        WHERE p1.`id_xallegro_product` > p2.`id_xallegro_product`
            AND p1.`id_product` = p2.`id_product`
            AND p1.`id_product_attribute` = p2.`id_product_attribute`'
    );

    $products = Db::getInstance()->executeS('
        SELECT `id_xallegro_product`, `description`, `images`
        FROM `' . _DB_PREFIX_ . 'xallegro_product`'
    );

    foreach ($products as $product) {
        if (!XAllegroApiTools::isSerialized($product['images'])) {
            continue;
        }

        Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'xallegro_product`
            SET `images` = "' . pSQL(json_encode(unserialize($product['images']))) . '",
                `description` = "' . pSQL(json_encode(array($product['description'], null, null, null))) . '"
            WHERE `id_xallegro_product` = ' . (int)$product['id_xallegro_product']
        );
    }
    // END individual product properties - migration -------------------------------------------------------------------

    // START template - migration --------------------------------------------------------------------------------------
    $templates = Db::getInstance()->executeS('
        SELECT `id_xallegro_template`, `additional_images`
        FROM `' . _DB_PREFIX_ . 'xallegro_template`'
    );

    foreach ($templates as $template) {
        if (!XAllegroApiTools::isSerialized($template['additional_images'])) {
            continue;
        }

        if ($template['additional_images']) {
            Db::getInstance()->execute('
                UPDATE `' . _DB_PREFIX_ . 'xallegro_template`
                SET `additional_images` = "' . pSQL(json_encode(unserialize($template['additional_images']))) . '"
                WHERE `id_xallegro_template` = ' . (int)$template['id_xallegro_template']
            );
        }
    }
    // END template - migration ----------------------------------------------------------------------------------------

    // START auction start_time - migration ----------------------------------------------------------------------------
    $auctions = Db::getInstance()->executeS('
        SELECT `id_xallegro_auction`, `start_time`
        FROM `' . _DB_PREFIX_ . 'xallegro_auction`'
    );

    foreach ($auctions as $auction) {
        if ($auction['start_time'] && isAuctionDateStart($auction['start_time'])) {
            $date = new DateTime($auction['start_time']);
            $date = $date->format('Y-m-d H:i:s');

            Db::getInstance()->execute('
                UPDATE `' . _DB_PREFIX_ . 'xallegro_auction`
                SET `start_time` = "' . pSQL($date) . '"
                WHERE `id_xallegro_auction` = ' . (int)$auction['id_xallegro_auction']
            );
        }
    }
    // END auction start_time - migration ------------------------------------------------------------------------------

    // START payment statuses - migration (PayU / Przelewy24) ----------------------------------------------------------
    $statuses = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'xallegro_status`');
    foreach ($statuses as $status) {
        if ($status['id_allegro_state'] == 'b') {
            $new_name = 'Przelew tradycyjny';
        } elseif ($status['position'] == 0) {
            $new_name = preg_replace('/^PayU - /', '', $status['allegro_name']);
        } else {
            $new_name = preg_replace('/^Status PayU/', 'Status Allegro', $status['allegro_name']);
        }

        Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'xallegro_status`
            SET `allegro_name` = "' . pSQL($new_name) . '"
            WHERE `id_xallegro_status` = ' . (int)$status['id_xallegro_status']
        );
    }

    // new statuses
    $sp = XAllegroConfiguration::get('PAYU_STATUS_SELECTED');
    Db::getInstance()->execute('
        INSERT IGNORE INTO `' . _DB_PREFIX_ . 'xallegro_status` (`id_order_state`, `id_allegro_state`, `allegro_name`, `position`)
        VALUES
        (' . $sp . ', "h",   "BPH",                   0),
        (' . $sp . ', "neb", "Nest Bank",             0),
        (' . $sp . ', "rap", "Raiffeisen R-Przelew",  0),
        (' . $sp . ', "plb", "Plus Bank",             0),
        (' . $sp . ', "bpo", "e-transfer Pocztowy24", 0),
        (' . $sp . ', "bsp", "Banki Spoldzielcze",    0)'
    );

    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ . 'xallegro_status`
        SET `allegro_name` = "Raty"
        WHERE `id_allegro_state` = "ai"'
    );

    $statuses_ids = XAllegroConfiguration::getMultiple(array(
        'PAYU_STATUS_INSTALLMENT',
        'PAYU_STATUS_SELECTED',
        'PAYU_STATUS_AWAITING'
    ));

    foreach ($statuses_ids as $status_id) {
        $os = new OrderState($status_id);
        if (Validate::isLoadedObject($os)) {
            foreach (Language::getLanguages() as $language) {
                $os->name[$language['id_lang']] = trim(preg_replace('/PayU$/', '', $os->name[$language['id_lang']]));
            }
            $os->save();
        }
    }
    // END payment statuses - migration (PayU / Przelewy24) ------------------------------------------------------------

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_account`
            DROP `password`,
            DROP `rest_apikey`,
            DROP `web_apikey`,
            DROP `verforms`,
            CHANGE `session_handle` `session` text NULL'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_product`
            ADD `tags` text NULL,
            ADD `images_additional` text NULL,
            ADD `title_pattern` char(128) NULL,
            ADD `sync_price` tinyint(1) NOT NULL DEFAULT 1,
            ADD `sync_quantity_allegro` tinyint(1) NOT NULL DEFAULT 1,
            MODIFY `id_product_attribute` int(10) unsigned NULL,
            CHANGE `images` `images_positions` text NULL,
            CHANGE `description` `descriptions_additional` text NULL,
            ADD UNIQUE KEY (`id_product`, `id_product_attribute`)'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_category`
            DROP `features`,
            ADD `fields_mapping` longtext NULL,
            ADD `tags` text NULL,
            CHANGE `fields` `fields_values` longtext NULL'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_pas`
            DROP `shipment_email`,
            DROP `customer_pickup`,
            DROP `account_number`,
            DROP `account_number_second`,
            ADD `country` int(10) unsigned NOT NULL'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_auction`
            DROP `account_number`,
            DROP `account_number_second`,
            MODIFY `start_time` datetime NULL'
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_manufacturer` (
            `id_xallegro_manufacturer`  int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_manufacturer`           int(10) unsigned NOT NULL,
            `tags`                      text NULL,

            PRIMARY KEY (`id_xallegro_manufacturer`),
            KEY (`id_manufacturer`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_carrier_package_info` (
            `id_order_carrier`          int(10) unsigned NOT NULL DEFAULT 0,
            `id_order`                  int(10) unsigned NOT NULL DEFAULT 0,
            `id_operator`               int(10) unsigned NOT NULL DEFAULT 0,
            `operator_name`             char(32) NULL,

            PRIMARY KEY(`id_order_carrier`),
            KEY(`id_order_carrier`, `id_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro__cats_form_fields` (
            `id_category`               int(10) unsigned NOT NULL,
            `sell_form_id`              int(10) unsigned NOT NULL,
            `sell_form_title`           char(255),
            `sell_form_cat`             int(10),
            `sell_form_type`            int(10),
            `sell_form_res_type`        int(10),
            `sell_form_def_value`       int(10),
            `sell_form_opt`             int(10),
            `sell_form_pos`             int(10),
            `sell_form_length`          int(10),
            `sell_min_value`            decimal(10,2),
            `sell_max_value`            decimal(10,2),
            `sell_form_desc`            text,
            `sell_form_opts_values`     text,
            `sell_form_field_desc`      text,
            `sell_form_param_id`        int(10) unsigned,
            `sell_form_param_values`    text,
            `sell_form_parent_id`       int(10) unsigned,
            `sell_form_parent_value`    int(10),
            `sell_form_unit`            char(16),
            `sell_form_options`         int(10),

            PRIMARY KEY(`id_category`, `sell_form_id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'xallegro__sell_form_fields`');

    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ . 'xallegro_account`
        SET `expire_session` = "2016-01-01 00:00:00"'
    );

    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ . 'xallegro_pas`
        SET `country` = ' . (int)XAllegroApi::COUNTRY_PL
    );

    // delete unused configuration
    foreach (array(
         'INPOST_LETTER',
         'INPOST_MACHINE',
         'INPOST_MACHINE_COD',
         'INPOST_CARRIER',
         'INPOST_CARRIER_COD',
         'SEND_ADDITIONAL_IMAGES'
     ) as $conf) {
        XAllegroConfiguration::deleteByName($conf);
    }

    // new configuration
    foreach (array(
         'PRODUCT_ASSOC_CLOSE_DELETED' => 0,
         'PRODUCT_ASSOC_CLOSE_UNACTIVE' => 0,
         'ORDER_SEND_CUSTOMER_MAIL' => 0,
         'PRICE_UPDATE' => 0,
         'PRICE_UPDATE_CHUNK' => 100,
         'PRICE_UPDATE_OFFSET' => 0,
         'FRONT_DISPLAY_LINK' => 0,
         'FRONT_DISPLAY_LINK_HOOK' => (version_compare(_PS_VERSION_, '1.7.0.0', '>=') ? 'displayProductAdditionalInfo' : 'displayLeftColumnProduct')
     ) as $key => $conf) {
        XAllegroConfiguration::updateValue($key, $conf);
    }

    // install demo templates
    $isNewTemplate = (int)Db::getInstance()->getValue('
        SELECT COUNT(*)
        FROM `' . _DB_PREFIX_ . 'xallegro_template`
        WHERE `is_new` = 1'
    );
    if (!$isNewTemplate) {
        $module->installDemoTemplates();
    }

    $module->reinstallMetas();

    return true;
}

function isAuctionDateStart($datetime, $format = 'd.m.Y H:i')
{
    $d = DateTime::createFromFormat($format, $datetime);
    return $d && $d->format($format) == $datetime;
}
