<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Adapter\DbAdapter;

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_4_0_0($module)
{
    $dbCharset = DbAdapter::getUtf8Collation();

    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'xallegro__cats_list`');
    Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'xallegro__sell_form_fields`');

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_account`
            MODIFY `password` varchar(255) NOT NULL,
            ADD `verkey` bigint(20) unsigned NOT NULL DEFAULT 0,
            ADD `vercats` char(16) NOT NULL DEFAULT 0,
            ADD `verforms` char(16) NOT NULL DEFAULT 0'
    );

    Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_category` ADD `features` longtext NULL');

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro__cats_list` (
            `country_code`              int(10) unsigned NOT NULL,
            `sandbox`                   tinyint(1) unsigned NOT NULL,
            `id`                        int(10) unsigned NOT NULL,
            `name`                      varchar(128),
            `parent_id`                 int(10) unsigned,
            `position`                  int(10) unsigned,

            PRIMARY KEY(`country_code`, `sandbox`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro__sell_form_fields` (
            `country_code`              int(10) unsigned NOT NULL,
            `sandbox`                   tinyint(1) unsigned NOT NULL,
            `sell_form_id`              int(10) unsigned NOT NULL,
            `sell_form_title`           varchar(255),
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
            `sell_form_unit`            varchar(16),
            `sell_form_options`         int(10) unsigned,

            PRIMARY KEY(`country_code`, `sandbox`, `sell_form_id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=' . $dbCharset
    );

    foreach (array(
         'INPOST_LETTER' => 7.20,
         'INPOST_MACHINE' => 8.60,
         'INPOST_MACHINE_COD' => 12.10,
         'INPOST_CARRIER' => 12.18,
         'INPOST_CARRIER_COD' => 15.68,
         'LOCK_SYNC' => 0,
         'PAGE_SYNC' => 0,

         // not longer supported settings, set to default
         'OVERLAY_UNDER_IMAGE' => 0,
         'XALLEGRO_RENEWAL_SETTINGS' => 4
     ) as $key => $value
    ) {
        XAllegroConfiguration::updateValue($key, $value);
    }

    $module->reinstallTabs();
    $module->registerHook('actionAdminControllerSetMedia');
    $module->uninstallOverrides();

    return true;
}
