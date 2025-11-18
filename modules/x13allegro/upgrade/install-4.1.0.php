<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Adapter\DbAdapter;

/**
 * @return bool
 */
function upgrade_module_4_1_0()
{
    $dbCharset = DbAdapter::getUtf8Collation();

    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_account`
            DROP `shop`,
            DROP `country`,
            DROP `country_code`,
            ADD `rest_apikey` char(255) NOT NULL AFTER `password`,
            ADD `client_secret` char(255) NOT NULL AFTER `password`,
            ADD `client_id` char(255) NOT NULL AFTER `password`,
            ADD `return_policy` char(64) NULL AFTER `sandbox`,
            ADD `warranty` char(64) NULL AFTER `sandbox`,
            ADD `implied_warranty` char(64) NULL AFTER `sandbox`,
            ADD `access_token` text NULL,
            ADD `refresh_token` text NULL,
            ADD `expire_authorization` datetime NOT NULL DEFAULT "2016-01-01 00:00:00",
            ADD `expire_refresh` datetime NOT NULL DEFAULT "2016-01-01 00:00:00",
            MODIFY `password` char(255) NULL,
            CHANGE `apikey` `web_apikey` char(255) NOT NULL'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_auction`
            ADD `id_xallegro_account` int(10) unsigned NOT NULL AFTER `id_xallegro_auction`'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_pas`
            MODIFY `invoice` int(10) unsigned NOT NULL'
    );

    Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'xallegro_site`');
    Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'xallegro__cats_list`');
    Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'xallegro__sell_form_fields`');

    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ . 'xallegro_pas`
            SET `invoice` = 32
        WHERE `invoice` = 1'
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro__cats_list` (
            `sandbox`                   tinyint(1) unsigned NOT NULL,
            `id`                        int(10) unsigned NOT NULL,
            `name`                      char(128) NULL,
            `parent_id`                 int(10) unsigned NULL,
            `position`                  int(10) unsigned NULL,
            `is_leaf`                   tinyint(1) unsigned NULL,

            PRIMARY KEY(`sandbox`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro__sell_form_fields` (
            `sandbox`                   tinyint(1) unsigned NOT NULL,
            `sell_form_id`              int(10) unsigned NOT NULL,
            `sell_form_title`           char(255),
            `sell_form_cat`             int(10) unsigned,
            `sell_form_type`            int(10) unsigned,
            `sell_form_res_type`        int(10) unsigned,
            `sell_form_def_value`       int(10) unsigned,
            `sell_form_opt`             int(10) unsigned,
            `sell_form_pos`             int(10) unsigned,
            `sell_form_length`          int(10) unsigned,
            `sell_min_value`            decimal(10,2),
            `sell_max_value`            decimal(10,2),
            `sell_form_desc`            text,
            `sell_form_opts_values`     text,
            `sell_form_field_desc`      text,
            `sell_form_param_id`        int(10) unsigned,
            `sell_form_param_values`    text,
            `sell_form_parent_id`       int(10) unsigned,
            `sell_form_parent_value`    int(10) unsigned,
            `sell_form_unit`            char(16),
            `sell_form_options`         int(10) unsigned,

            PRIMARY KEY(`sandbox`, `sell_form_id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ . 'xallegro_auction`
            SET `id_xallegro_account` = `id_allegro_account`'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_auction`
            DROP INDEX `id_allegro_account`,
            DROP PRIMARY KEY,
            DROP `id_allegro_account`,
            ADD PRIMARY KEY (`id_xallegro_auction`, `id_xallegro_account`)'
    );

    return true;
}
